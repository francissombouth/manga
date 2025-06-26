<?php

namespace App\Command;

use App\Service\MangaDxService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:count-mangas',
    description: 'Compte le nombre total de mangas disponibles sur l\'API MangaDx',
)]
class CountMangasCommand extends Command
{
    private const MANGADX_API_BASE = 'https://api.mangadex.org';

    public function __construct(
        private MangaDxService $mangaDxService,
        private HttpClientInterface $httpClient
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title("Comptage des mangas disponibles sur MangaDx");

        try {
            // Faire une requête pour obtenir le total sans récupérer les données
            $io->text("🔍 Interrogation de l'API MangaDx...");
            
            $response = $this->httpClient->request('GET', self::MANGADX_API_BASE . '/manga', [
                'query' => [
                    'limit' => 1, // On ne récupère qu'un seul résultat pour avoir le total
                    'contentRating' => ['safe', 'suggestive', 'erotica'], // Tous sauf pornographic
                    'availableTranslatedLanguage' => ['en', 'fr'], // Langues supportées
                    'hasAvailableChapters' => 'true', // Seulement ceux avec des chapitres
                    'order' => ['followedCount' => 'desc'] // Tri par popularité
                ],
                'headers' => [
                    'User-Agent' => 'MangaTheque/1.0 (Educational Project)'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                $io->error("Erreur HTTP : " . $response->getStatusCode());
                return Command::FAILURE;
            }

            $data = $response->toArray();
            $total = $data['total'] ?? 0;

            $io->section("📊 Résultats");
            
            $io->createTable()
                ->setHeaders(['Statistique', 'Valeur'])
                ->setRows([
                    ['📚 Total mangas disponibles', number_format($total, 0, ',', ' ')],
                    ['🌍 Langues', 'Français + Anglais'],
                    ['🔞 Content Rating', 'Safe + Suggestive + Erotica'],
                    ['📖 Avec chapitres', 'Oui']
                ])
                ->render();

            // Estimation du temps d'import
            $estimatedBatches = ceil($total / 100); // 100 par batch
            $estimatedMinutes = ceil($estimatedBatches * 2); // ~2 minutes par batch de 100

            $io->section("⏱️ Estimation d'import complet");
            $io->text("📦 Nombre de lots (100 mangas/lot) : " . number_format($estimatedBatches, 0, ',', ' '));
            $io->text("⏱️  Temps estimé : ~{$estimatedMinutes} minutes");
            $io->text("💾 Espace disque estimé : ~" . round($total * 0.05, 1) . " MB");

            $io->section("🚀 Commande d'import complet");
            $io->text("Pour importer tous les mangas, utilisez :");
            $io->text("<info>php bin/console app:import-all-mangas --user-id=36</info>");

            if ($total > 5000) {
                $io->warning("⚠️  Grand volume de données détecté ! Assurez-vous d'avoir :");
                $io->listing([
                    'Une connexion internet stable',
                    'Suffisamment d\'espace disque',
                    'Du temps disponible (~' . $estimatedMinutes . ' minutes)',
                    'Surveillance du processus recommandée'
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error("Erreur lors du comptage : " . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 