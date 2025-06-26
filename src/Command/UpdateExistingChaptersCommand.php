<?php

namespace App\Command;

use App\Entity\Oeuvre;
use App\Repository\OeuvreRepository;
use App\Service\MangaDxService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-existing-chapters',
    description: 'Met à jour les chapitres existants avec leur mangadxChapterId',
)]
class UpdateExistingChaptersCommand extends Command
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private MangaDxService $mangaDxService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Mise à jour des chapitres existants avec mangadxChapterId');

        // Récupérer les œuvres avec un mangadxId
        $oeuvres = $this->oeuvreRepository->findAll();
        $oeuvresAvecMangadxId = [];
        
        foreach ($oeuvres as $oeuvre) {
            if ($oeuvre->getMangadxId() && !empty(trim($oeuvre->getMangadxId()))) {
                $oeuvresAvecMangadxId[] = $oeuvre;
            }
        }

        if (empty($oeuvresAvecMangadxId)) {
            $io->error('Aucune œuvre avec un mangadxId valide trouvée');
            return Command::FAILURE;
        }

        $io->info('Œuvres à mettre à jour : ' . count($oeuvresAvecMangadxId));

        $totalUpdated = 0;
        $totalChapters = 0;

        foreach ($oeuvresAvecMangadxId as $oeuvre) {
            $io->section("Mise à jour de : {$oeuvre->getTitre()} (MangaDx ID: {$oeuvre->getMangadxId()})");

            try {
                // Récupérer les chapitres depuis l'API MangaDx
                $chaptersData = $this->mangaDxService->getAllMangaChapters($oeuvre->getMangadxId());
                
                if (empty($chaptersData)) {
                    $io->text("❌ Aucun chapitre trouvé dans l'API");
                    continue;
                }

                $io->text("Chapitres trouvés dans l'API : " . count($chaptersData));
                
                $updatedChapters = 0;
                $chapitres = $oeuvre->getChapitres();
                
                foreach ($chapitres as $chapitre) {
                    // Chercher le chapitre correspondant dans l'API par numéro
                    $chapterNumber = $chapitre->getOrdre();
                    $foundChapter = null;
                    
                    foreach ($chaptersData as $chapterData) {
                        $apiChapterNumber = (float) ($chapterData['attributes']['chapter'] ?? 0);
                        if ($apiChapterNumber == $chapterNumber) {
                            $foundChapter = $chapterData;
                            break;
                        }
                    }
                    
                    if ($foundChapter && !$chapitre->getMangadxChapterId()) {
                        $chapitre->setMangadxChapterId($foundChapter['id']);
                        $updatedChapters++;
                        $totalUpdated++;
                        
                        $io->text("  ✅ Chapitre {$chapterNumber}: {$chapitre->getTitre()}");
                        $io->text("     - MangaDx Chapter ID: {$foundChapter['id']}");
                    } elseif ($chapitre->getMangadxChapterId()) {
                        $io->text("  ℹ️  Chapitre {$chapterNumber}: déjà mis à jour");
                    } else {
                        $io->text("  ❌ Chapitre {$chapterNumber}: non trouvé dans l'API");
                    }
                }
                
                $totalChapters += count($chapitres);
                $io->text("Chapitres mis à jour pour cette œuvre: {$updatedChapters}/" . count($chapitres));
                
            } catch (\Exception $e) {
                $io->text("❌ Erreur lors de la mise à jour: " . $e->getMessage());
            }
            
            $io->newLine();
        }

        // Sauvegarder les modifications
        $this->entityManager->flush();

        $io->success([
            "Mise à jour terminée !",
            "Œuvres traitées: " . count($oeuvresAvecMangadxId),
            "Total chapitres: {$totalChapters}",
            "Chapitres mis à jour: {$totalUpdated}"
        ]);

        return Command::SUCCESS;
    }
} 