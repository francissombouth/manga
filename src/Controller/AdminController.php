<?php

namespace App\Controller;

use App\Entity\Oeuvre;
use App\Entity\Chapitre;
use App\Entity\Auteur;
use App\Entity\Tag;
use App\Repository\OeuvreRepository;
use App\Repository\ChapitreRepository;
use App\Repository\AuteurRepository;
use App\Repository\TagRepository;
use App\Service\MangaDxService;
use App\Service\MangaDxImportService;
use App\Service\AdminPagesService;
use App\Form\OeuvreType;
use App\Form\ChapitreType;
use App\Form\AuteurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(
        private OeuvreRepository $oeuvreRepository,
        private ChapitreRepository $chapitreRepository,
        private AuteurRepository $auteurRepository,
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
        private MangaDxService $mangaDxService,
        private MangaDxImportService $importService,
        private AdminPagesService $adminPagesService
    ) {
    }

    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $stats = [
            'total_oeuvres' => $this->oeuvreRepository->count([]),
            'total_chapitres' => $this->chapitreRepository->count([]),
            'total_auteurs' => $this->auteurRepository->count([]),
            'total_tags' => $this->tagRepository->count([]),
        ];

        $recentOeuvres = $this->oeuvreRepository->findBy([], ['updatedAt' => 'DESC'], 5);
        $recentChapitres = $this->chapitreRepository->findBy([], ['createdAt' => 'DESC'], 5);

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'recent_oeuvres' => $recentOeuvres,
            'recent_chapitres' => $recentChapitres,
        ]);
    }

    // GESTION DES ŒUVRES
    #[Route('/oeuvres', name: 'admin_oeuvres')]
    public function oeuvres(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $search = $request->query->get('search', '');

        // AUTO-ALIMENTATION : Si moins de 100 œuvres en BDD, importer depuis l'API
        $totalOeuvres = $this->oeuvreRepository->count([]);
        if ($totalOeuvres < 100) {
            $this->autoImportFromCatalog();
        }

        if ($search) {
            $oeuvres = $this->oeuvreRepository->findByTitre($search);
            $total = count($oeuvres);
            $oeuvres = array_slice($oeuvres, ($page - 1) * $limit, $limit);
        } else {
            $oeuvres = $this->oeuvreRepository->findBy([], ['updatedAt' => 'DESC'], $limit, ($page - 1) * $limit);
            $total = $this->oeuvreRepository->count([]);
        }

        $totalPages = ceil($total / $limit);

        return $this->render('admin/oeuvres/list.html.twig', [
            'oeuvres' => $oeuvres,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'total' => $total
        ]);
    }

    #[Route('/oeuvres/new', name: 'admin_oeuvre_new')]
    public function newOeuvre(Request $request): Response
    {
        $oeuvre = new Oeuvre();
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier que l'ID MangaDx n'existe pas déjà (seulement si fourni)
            if ($oeuvre->getMangadxId()) {
                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $oeuvre->getMangadxId()]);
                if ($existingOeuvre) {
                    $this->addFlash('error', 'Une œuvre avec cet ID MangaDx existe déjà dans la base de données.');
                    return $this->render('admin/oeuvres/form.html.twig', [
                        'form' => $form->createView(),
                        'oeuvre' => $oeuvre,
                        'title' => 'Ajouter une œuvre'
                    ]);
                }
            }

            $this->oeuvreRepository->save($oeuvre, true);
            $this->addFlash('success', 'L\'œuvre a été ajoutée avec succès !');
            return $this->redirectToRoute('admin_oeuvres');
        }

        return $this->render('admin/oeuvres/form.html.twig', [
            'form' => $form->createView(),
            'oeuvre' => $oeuvre,
            'title' => 'Ajouter une œuvre'
        ]);
    }

    #[Route('/oeuvres/{id}/edit', name: 'admin_oeuvre_edit')]
    public function editOeuvre(Oeuvre $oeuvre, Request $request): Response
    {
        $form = $this->createForm(OeuvreType::class, $oeuvre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $oeuvre->setUpdatedAt(new \DateTimeImmutable());
            $this->oeuvreRepository->save($oeuvre, true);
            $this->addFlash('success', 'L\'œuvre a été modifiée avec succès !');
            return $this->redirectToRoute('admin_oeuvres');
        }

        return $this->render('admin/oeuvres/form.html.twig', [
            'form' => $form->createView(),
            'oeuvre' => $oeuvre,
            'title' => 'Modifier l\'œuvre'
        ]);
    }

    #[Route('/oeuvres/{id}/delete', name: 'admin_oeuvre_delete', methods: ['POST'])]
    public function deleteOeuvre(Oeuvre $oeuvre): Response
    {
        $this->oeuvreRepository->remove($oeuvre, true);
        $this->addFlash('success', 'L\'œuvre a été supprimée avec succès !');
        return $this->redirectToRoute('admin_oeuvres');
    }

    // GESTION DES CHAPITRES
    #[Route('/oeuvres/{id}/chapitres', name: 'admin_oeuvre_chapitres')]
    public function oeuvreChapitres(Oeuvre $oeuvre): Response
    {
        $chapitres = $this->chapitreRepository->findByOeuvre($oeuvre->getId());

        // Récupérer les pages dynamiquement pour chaque chapitre (comme le catalogue)
        $chapitresAvecPages = [];
        foreach ($chapitres as $chapitre) {
            $pages = $this->adminPagesService->getChapitrePages($chapitre);
            $chapitresAvecPages[] = [
                'chapitre' => $chapitre,
                'pages' => $pages,
                'pages_count' => count($pages),
                'peut_recuperer_pages' => $chapitre->peutRecupererPagesDynamiques()
            ];
        }

        return $this->render('admin/chapitres/list.html.twig', [
            'oeuvre' => $oeuvre,
            'chapitres' => $chapitresAvecPages
        ]);
    }

    #[Route('/oeuvres/{id}/chapitres/new', name: 'admin_chapitre_new')]
    public function newChapitre(Oeuvre $oeuvre, Request $request): Response
    {
        $chapitre = new Chapitre();
        $chapitre->setOeuvre($oeuvre);
        
        // Définir l'ordre automatiquement
        $lastChapitre = $this->chapitreRepository->findOneBy(['oeuvre' => $oeuvre], ['ordre' => 'DESC']);
        $chapitre->setOrdre($lastChapitre ? $lastChapitre->getOrdre() + 1 : 1);

        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->chapitreRepository->save($chapitre, true);
            $this->addFlash('success', 'Le chapitre a été créé avec succès !');
            return $this->redirectToRoute('admin_oeuvre_chapitres', ['id' => $oeuvre->getId()]);
        }

        return $this->render('admin/chapitres/form.html.twig', [
            'form' => $form->createView(),
            'chapitre' => $chapitre,
            'oeuvre' => $oeuvre,
            'title' => 'Nouveau chapitre'
        ]);
    }

    #[Route('/chapitres/{id}/edit', name: 'admin_chapitre_edit')]
    public function editChapitre(Chapitre $chapitre, Request $request): Response
    {
        $form = $this->createForm(ChapitreType::class, $chapitre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $chapitre->setUpdatedAt(new \DateTimeImmutable());
            $this->chapitreRepository->save($chapitre, true);
            $this->addFlash('success', 'Le chapitre a été modifié avec succès !');
            return $this->redirectToRoute('admin_oeuvre_chapitres', ['id' => $chapitre->getOeuvre()->getId()]);
        }

        return $this->render('admin/chapitres/form.html.twig', [
            'form' => $form->createView(),
            'chapitre' => $chapitre,
            'oeuvre' => $chapitre->getOeuvre(),
            'title' => 'Modifier le chapitre'
        ]);
    }

    #[Route('/chapitres/{id}/delete', name: 'admin_chapitre_delete', methods: ['POST'])]
    public function deleteChapitre(Chapitre $chapitre): Response
    {
        $oeuvreId = $chapitre->getOeuvre()->getId();
        $this->chapitreRepository->remove($chapitre, true);
        $this->addFlash('success', 'Le chapitre a été supprimé avec succès !');
        return $this->redirectToRoute('admin_oeuvre_chapitres', ['id' => $oeuvreId]);
    }

    #[Route('/import-mangadx', name: 'admin_import_mangadx', methods: ['GET', 'POST'])]
    public function importMangaDx(Request $request, MangaDxImportService $importService): Response
    {
        if ($request->isMethod('POST')) {
            $mangadxId = $request->request->get('mangadx_id');
            
            if ($mangadxId) {
                try {
                    $oeuvre = $importService->importOrUpdateOeuvre($mangadxId);
                    
                    if ($oeuvre) {
                        $this->addFlash('success', sprintf(
                            'Œuvre "%s" importée avec succès ! (%d chapitre(s) ajouté(s))',
                            $oeuvre->getTitre(),
                            count($oeuvre->getChapitres())
                        ));
                        
                        return $this->redirectToRoute('admin_oeuvre_show', ['id' => $oeuvre->getId()]);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Veuillez saisir un ID MangaDx valide.');
            }
        }

        return $this->render('admin/import_mangadx.html.twig');
    }

    #[Route('/import-popular', name: 'admin_import_popular', methods: ['GET', 'POST'])]
    public function importPopular(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $limit = (int) $request->request->get('limit', 20);
            $rating = $request->request->get('rating', 'safe');
            $status = $request->request->get('status', '');
            $dryRun = $request->request->get('dry_run', false);

            try {
                // Construire la commande
                $command = 'php bin/console app:import-popular-mangas';
                $command .= ' --limit=' . $limit;
                $command .= ' --rating=' . $rating;
                if ($status) {
                    $command .= ' --status=' . $status;
                }
                if ($dryRun) {
                    $command .= ' --dry-run';
                }

                // Exécuter la commande en arrière-plan
                if (PHP_OS_FAMILY === 'Windows') {
                    $output = shell_exec($command . ' 2>&1');
                } else {
                    $output = shell_exec($command . ' 2>&1');
                }

                if ($dryRun) {
                    $this->addFlash('info', 'Simulation terminée. Consultez les logs pour voir les résultats.');
                } else {
                    $this->addFlash('success', 'Import massif lancé ! L\'opération peut prendre plusieurs minutes.');
                }

                // Optionnel : afficher la sortie de la commande
                if ($output) {
                    $this->addFlash('info', 'Sortie de la commande : ' . substr($output, 0, 500) . '...');
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du lancement de l\'import : ' . $e->getMessage());
            }
        }

        return $this->render('admin/import_popular.html.twig');
    }

    #[Route('/import-massive', name: 'admin_import_massive', methods: ['GET', 'POST'])]
    public function importMassive(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $limit = (int) $request->request->get('limit', 50);
            $force = $request->request->get('force', false);
            $dryRun = $request->request->get('dry_run', false);

            try {
                // Construire la commande
                $command = 'php bin/console app:import-massive-data';
                $command .= ' --limit=' . $limit;
                if ($force) {
                    $command .= ' --force';
                }
                if ($dryRun) {
                    $command .= ' --dry-run';
                }

                // Exécuter la commande en arrière-plan
                if (PHP_OS_FAMILY === 'Windows') {
                    $output = shell_exec($command . ' 2>&1');
                } else {
                    $output = shell_exec($command . ' 2>&1');
                }

                if ($dryRun) {
                    $this->addFlash('info', 'Simulation terminée. Consultez les logs pour voir les résultats.');
                } else {
                    $this->addFlash('success', 'Import massif de données lancé ! ' . $limit . ' œuvres ont été générées.');
                }

                // Optionnel : afficher la sortie de la commande
                if ($output && strpos($output, 'importées avec succès') !== false) {
                    $this->addFlash('success', 'Import terminé avec succès !');
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors du lancement de l\'import : ' . $e->getMessage());
            }
        }

        return $this->render('admin/import_massive.html.twig');
    }

    /**
     * Auto-importe des œuvres depuis le catalogue MangaDx pour alimenter l'administration
     */
    private function autoImportFromCatalog(): void
    {
        try {
            // Importer 96 œuvres populaires pour avoir un bon catalogue d'administration
            $popularMangasData = $this->mangaDxService->getPopularManga(96, 0);
            
            $importedCount = 0;
            foreach ($popularMangasData as $mangaData) {
                try {
                    // Vérifier si l'œuvre existe déjà
                    $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangaData['id']]);
                    if (!$existingOeuvre) {
                        // Importer l'œuvre avec tous ses chapitres et détails
                        $oeuvre = $this->importService->importOrUpdateOeuvre($mangaData['id']);
                        if ($oeuvre) {
                            $importedCount++;
                        }
                    }
                    
                    // Limiter à 50 imports pour éviter les timeouts
                    if ($importedCount >= 50) {
                        break;
                    }
                } catch (\Exception $e) {
                    // Si l'import d'une œuvre échoue, on continue avec les autres
                    continue;
                }
            }
            
            if ($importedCount > 0) {
                $this->addFlash('success', "$importedCount nouvelles œuvres ont été automatiquement importées depuis le catalogue MangaDx !");
            }
        } catch (\Exception $e) {
            // Si l'API ne répond pas, on utilise la commande de génération locale
            try {
                shell_exec('php bin/console app:import-massive-data --limit=50 > /dev/null 2>&1 &');
                $this->addFlash('info', "Auto-génération de données lancée (API MangaDx indisponible)");
            } catch (\Exception $e) {
                // Si tout échoue, on ignore silencieusement
            }
        }
    }

    // GESTION DES AUTEURS
    #[Route('/auteurs', name: 'admin_auteurs')]
    public function auteurs(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $search = $request->query->get('search', '');

        if ($search) {
            $auteurs = $this->auteurRepository->findByNom($search);
            $total = count($auteurs);
            $auteurs = array_slice($auteurs, ($page - 1) * $limit, $limit);
        } else {
            $auteurs = $this->auteurRepository->findBy([], ['nom' => 'ASC'], $limit, ($page - 1) * $limit);
            $total = $this->auteurRepository->count([]);
        }

        $totalPages = ceil($total / $limit);

        return $this->render('admin/auteurs/list.html.twig', [
            'auteurs' => $auteurs,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'total' => $total
        ]);
    }

    #[Route('/auteurs/new', name: 'admin_auteur_new')]
    public function newAuteur(Request $request): Response
    {
        $auteur = new Auteur();
        $form = $this->createForm(AuteurType::class, $auteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->auteurRepository->save($auteur, true);
            $this->addFlash('success', 'L\'auteur a été ajouté avec succès !');
            return $this->redirectToRoute('admin_auteurs');
        }

        return $this->render('admin/auteurs/form.html.twig', [
            'form' => $form->createView(),
            'auteur' => $auteur,
            'title' => 'Ajouter un auteur'
        ]);
    }

    #[Route('/auteurs/{id}/edit', name: 'admin_auteur_edit')]
    public function editAuteur(Auteur $auteur, Request $request): Response
    {
        $form = $this->createForm(AuteurType::class, $auteur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $auteur->setUpdatedAt(new \DateTimeImmutable());
            $this->auteurRepository->save($auteur, true);
            $this->addFlash('success', 'L\'auteur a été modifié avec succès !');
            return $this->redirectToRoute('admin_auteurs');
        }

        return $this->render('admin/auteurs/form.html.twig', [
            'form' => $form->createView(),
            'auteur' => $auteur,
            'title' => 'Modifier l\'auteur'
        ]);
    }

    #[Route('/auteurs/{id}/delete', name: 'admin_auteur_delete', methods: ['POST'])]
    public function deleteAuteur(Auteur $auteur): Response
    {
        // Vérifier si l'auteur a des œuvres
        if (count($auteur->getOeuvres()) > 0) {
            $this->addFlash('error', 'Impossible de supprimer cet auteur car il a des œuvres associées.');
            return $this->redirectToRoute('admin_auteurs');
        }

        $this->auteurRepository->remove($auteur, true);
        $this->addFlash('success', 'L\'auteur a été supprimé avec succès !');
        return $this->redirectToRoute('admin_auteurs');
    }
} 