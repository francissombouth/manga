<?php

namespace App\Command;

use App\Repository\OeuvreRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug-covers',
    description: 'Debug les images de couverture du catalogue',
)]
class DebugCoversCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('🔍 Debug des couvertures du catalogue');
        
        // Test 1: Vérifier les œuvres avec couvertures
        $oeuvres = $this->oeuvreRepository->findBy([], ['id' => 'ASC'], 5);
        
        $io->section('📊 Échantillon des 5 premières œuvres');
        
        $rows = [];
        foreach ($oeuvres as $oeuvre) {
            $rows[] = [
                $oeuvre->getId(),
                $oeuvre->getTitre(),
                $oeuvre->getCouverture() ? '✅ Oui' : '❌ Non',
                $oeuvre->getCouverture() ? substr($oeuvre->getCouverture(), 0, 50) . '...' : 'Aucune'
            ];
        }
        
        $io->table(['ID', 'Titre', 'A une couverture', 'URL (tronquée)'], $rows);
        
        // Test 2: Statistiques générales
        $totalOeuvres = $this->oeuvreRepository->count([]);
        $avecCouverture = $this->oeuvreRepository->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.couverture IS NOT NULL')
            ->andWhere('o.couverture != :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->getSingleScalarResult();
            
        $io->section('📈 Statistiques générales');
        $io->createTable()
            ->setHeaders(['Métrique', 'Valeur'])
            ->setRows([
                ['Total œuvres', $totalOeuvres],
                ['Avec couverture', $avecCouverture],
                ['Pourcentage', round(($avecCouverture / $totalOeuvres) * 100, 1) . '%']
            ])
            ->render();
            
        // Test 3: Test d'une URL de couverture
        $oeuvreAvecCouverture = $this->oeuvreRepository->createQueryBuilder('o')
            ->where('o.couverture IS NOT NULL')
            ->andWhere('o.couverture != :empty')
            ->setParameter('empty', '')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
            
        if ($oeuvreAvecCouverture) {
            $io->section('🧪 Test d\'une URL de couverture');
            $io->text("Œuvre: " . $oeuvreAvecCouverture->getTitre());
            $io->text("URL: " . $oeuvreAvecCouverture->getCouverture());
            
            // Test si l'URL répond
            try {
                $headers = @get_headers($oeuvreAvecCouverture->getCouverture(), 1);
                if ($headers && strpos($headers[0], '200') !== false) {
                    $io->success('✅ URL accessible directement');
                } else {
                    $io->warning('⚠️ URL non accessible directement (normal, nécessite proxy)');
                }
            } catch (\Exception $e) {
                $io->warning('⚠️ Test URL échoué: ' . $e->getMessage());
            }
            
            // URL du proxy
            $proxyUrl = "https://127.0.0.1:8000/proxy/image?url=" . urlencode($oeuvreAvecCouverture->getCouverture());
            $io->text("URL Proxy: " . $proxyUrl);
            $io->info('💡 Testez cette URL dans votre navigateur pour vérifier le proxy');
        }
        
        return Command::SUCCESS;
    }
} 