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
use App\Form\TagType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\MangaDxTagService;

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
        $search = (string) $request->query->get('search', '');

        if ($search) {
            $oeuvres = $this->oeuvreRepository->findByTitre($search);
            $total = count($oeuvres);
            $oeuvres = array_slice($oeuvres, ($page - 1) * $limit, $limit);
        } else {
            $oeuvres = $this->oeuvreRepository->findAllWithRelations($limit, ($page - 1) * $limit);
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
        $oeuvreId = $oeuvre->getId();
        if ($oeuvreId === null) {
            throw new \InvalidArgumentException('L\'œuvre n\'a pas d\'ID valide');
        }
        
        $chapitres = $this->chapitreRepository->findByOeuvre($oeuvreId);

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
        $chapitre->setOrdre($lastChapitre instanceof \App\Entity\Chapitre ? $lastChapitre->getOrdre() + 1 : 1);

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
            
            $oeuvre = $chapitre->getOeuvre();
            if ($oeuvre === null) {
                throw new \InvalidArgumentException('Le chapitre n\'a pas d\'œuvre associée');
            }
            
            return $this->redirectToRoute('admin_oeuvre_chapitres', ['id' => $oeuvre->getId()]);
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
        $oeuvre = $chapitre->getOeuvre();
        if ($oeuvre === null) {
            throw new \InvalidArgumentException('Le chapitre n\'a pas d\'œuvre associée');
        }
        
        $oeuvreId = $oeuvre->getId();
        $this->chapitreRepository->remove($chapitre, true);
        $this->addFlash('success', 'Le chapitre a été supprimé avec succès !');
        return $this->redirectToRoute('admin_oeuvre_chapitres', ['id' => $oeuvreId]);
    }

    #[Route('/import-mangadx', name: 'admin_import_mangadx', methods: ['GET', 'POST'])]
    public function importMangaDx(Request $request, MangaDxImportService $importService): Response
    {
        if ($request->isMethod('POST')) {
            $mangadxId = (string) $request->request->get('mangadx_id', '');
            
            if ($mangadxId) {
                try {
                    $oeuvre = $importService->importOrUpdateOeuvre($mangadxId);
                    
                    if ($oeuvre) {
                        $this->addFlash('success', sprintf(
                            'Œuvre "%s" importée avec succès ! (%d chapitre(s) ajouté(s))',
                            $oeuvre->getTitre(),
                            count($oeuvre->getChapitres())
                        ));
                        
                        $oeuvreId = $oeuvre->getId();
                        if ($oeuvreId === null) {
                            throw new \InvalidArgumentException('L\'œuvre importée n\'a pas d\'ID valide');
                        }
                        
                        return $this->redirectToRoute('admin_oeuvre_edit', ['id' => $oeuvreId]);
                    }
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', 'Veuillez saisir un ID MangaDx valide.');
            }
            
            // Redirection après traitement du formulaire pour éviter l'erreur Turbo
            return $this->redirectToRoute('admin_import_mangadx');
        }

        // Fournir les statistiques pour le template
        $totalOeuvres = $this->oeuvreRepository->count([]);
        $totalChapitres = $this->chapitreRepository->count([]);

        return $this->render('admin/import_mangadx.html.twig', [
            'totalOeuvres' => $totalOeuvres,
            'totalChapitres' => $totalChapitres,
        ]);
    }

    #[Route('/import-massive', name: 'admin_import_massive', methods: ['GET', 'POST'])]
    public function importMassive(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $limit = (int) $request->request->get('limit', 10);
            $category = $request->request->get('category', 'popular');
            $force = $request->request->get('force', false);

            try {
                // Utiliser directement le service d'import au lieu de shell_exec
                $successes = 0;
                $errors = 0;
                
                // Vider la base si l'option force est cochée
                if ($force) {
                    $this->entityManager->createQuery('DELETE FROM App\Entity\Chapitre')->execute();
                    $this->entityManager->createQuery('DELETE FROM App\Entity\Oeuvre')->execute();
                    $this->entityManager->createQuery('DELETE FROM App\Entity\Auteur')->execute();
                    $this->entityManager->createQuery('DELETE FROM App\Entity\Tag')->execute();
                    $this->entityManager->flush();
                    $this->addFlash('warning', '🗑️ Base de données vidée avant import.');
                }
                
                // Pour garantir le nombre exact d'œuvres, on peut récupérer plus d'œuvres de l'API
                $offset = 0;
                $batchSize = $limit * 2; // Récupérer plus d'œuvres pour compenser celles qui existent déjà
                
                while ($successes < $limit && $offset < 500) { // Limite de sécurité à 500 pour éviter les boucles infinies
                    $oeuvresData = match($category) {
                        'popular' => $this->mangaDxService->getPopularManga($batchSize, $offset),
                        'latest' => $this->mangaDxService->getLatestManga($batchSize, $offset),
                        'random' => $this->mangaDxService->getRandomManga($batchSize),
                        default => $this->mangaDxService->getPopularManga($batchSize, $offset)
                    };
                    
                    if (empty($oeuvresData)) {
                        break; // Plus d'œuvres disponibles
                    }
                
                    foreach ($oeuvresData as $oeuvreData) {
                        if ($successes >= $limit) {
                            break; // On a atteint le nombre voulu
                        }
                        
                        try {
                            $mangadxId = $oeuvreData['id'];
                            
                            // Si force est activé, on importe tout sans vérifier l'existence
                            if ($force) {
                                $oeuvre = $this->importService->importOrUpdateOeuvre($mangadxId);
                                if ($oeuvre) {
                                    $successes++;
                                } else {
                                    $errors++;
                                }
                            } else {
                                // Vérifier si l'œuvre existe déjà
                                $existingOeuvre = $this->oeuvreRepository->findOneBy(['mangadxId' => $mangadxId]);
                                if (!$existingOeuvre) {
                                    // Importer l'œuvre complète
                                    $oeuvre = $this->importService->importOrUpdateOeuvre($mangadxId);
                                    if ($oeuvre) {
                                        $successes++;
                                    } else {
                                        $errors++;
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $errors++;
                        }
                    }
                    
                    $offset += $batchSize;
                }
                
                if ($successes > 0) {
                    $this->addFlash('success', "✅ {$successes} œuvres importées avec succès depuis MangaDx !");
                }
                if ($errors > 0) {
                    $this->addFlash('warning', "⚠️ {$errors} œuvres n'ont pas pu être importées.");
                }

            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'import : ' . $e->getMessage());
            }
            
            // Redirection après traitement du formulaire
            return $this->redirectToRoute('admin_import_massive');
        }

        return $this->render('admin/import_massive.html.twig');
    }



    // GESTION DES AUTEURS
    #[Route('/auteurs', name: 'admin_auteurs')]
    public function auteurs(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $search = (string) $request->query->get('search', '');

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

    // === GESTION DES UTILISATEURS ET RÔLES ===

    #[Route('/users', name: 'admin_users')]
    public function users(Request $request): Response
    {
        $userRepository = $this->entityManager->getRepository(\App\Entity\User::class);
        
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $search = $request->query->get('search', '');

        if ($search) {
            $users = $userRepository->createQueryBuilder('u')
                ->where('u.nom LIKE :search OR u.email LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('u.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
            $total = count($users);
            $users = array_slice($users, ($page - 1) * $limit, $limit);
        } else {
            $users = $userRepository->findBy([], ['createdAt' => 'DESC'], $limit, ($page - 1) * $limit);
            $total = $userRepository->count([]);
        }

        $totalPages = ceil($total / $limit);

        return $this->render('admin/users/list.html.twig', [
            'users' => $users,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'search' => $search,
            'total' => $total
        ]);
    }

    #[Route('/users/{id}/edit-roles', name: 'admin_user_edit_roles')]
    public function editUserRoles(\App\Entity\User $user, Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $roles = $request->request->all('roles');
            
            // Filtrer les rôles valides
            $validRoles = ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_SUPER_ADMIN'];
            $newRoles = array_intersect($roles, $validRoles);
            
            // ROLE_USER est toujours présent
            if (!in_array('ROLE_USER', $newRoles)) {
                $newRoles[] = 'ROLE_USER';
            }

            $user->setRoles($newRoles);
            $user->setUpdatedAt(new \DateTimeImmutable());
            
            $this->entityManager->flush();
            
            $this->addFlash('success', sprintf(
                'Les rôles de %s ont été mis à jour avec succès !',
                $user->getNom()
            ));
            
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit_roles.html.twig', [
            'user' => $user,
            'available_roles' => [
                'ROLE_USER' => 'Utilisateur',
                'ROLE_ADMIN' => 'Administrateur',
                'ROLE_SUPER_ADMIN' => 'Super Administrateur'
            ]
        ]);
    }

    #[Route('/users/{id}/toggle-admin', name: 'admin_user_toggle_admin', methods: ['POST'])]
    public function toggleAdminRole(\App\Entity\User $user): Response
    {
        if ($user->isAdmin()) {
            // Retirer les droits admin (garder seulement ROLE_USER)
            $user->setRoles(['ROLE_USER']);
            $message = sprintf('%s n\'est plus administrateur.', $user->getNom());
        } else {
            // Donner les droits admin
            $user->addRole('ROLE_ADMIN');
            $message = sprintf('%s est maintenant administrateur.', $user->getNom());
        }

        $user->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
        
        $this->addFlash('success', $message);
        
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/users/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function deleteUser(\App\Entity\User $user): Response
    {
        // Empêcher l'utilisateur de se supprimer lui-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('admin_users');
        }

        $userName = $user->getNom();
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        
        $this->addFlash('success', sprintf('L\'utilisateur %s a été supprimé avec succès.', $userName));
        
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/tags', name: 'admin_tags')]
    #[IsGranted('ROLE_ADMIN')]
    public function tags(TagRepository $tagRepository): Response
    {
        $tags = $tagRepository->findAll();
        
        return $this->render('admin/tags/list.html.twig', [
            'tags' => $tags,
            'title' => 'Gestion des Genres'
        ]);
    }

    #[Route('/tags/new', name: 'admin_tag_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function tagNew(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tag = new Tag();
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tag);
            $entityManager->flush();

            $this->addFlash('success', 'Genre créé avec succès !');
            return $this->redirectToRoute('admin_tags');
        }

        return $this->render('admin/tags/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Nouveau Genre',
            'tag' => $tag
        ]);
    }

    #[Route('/tags/{id}/edit', name: 'admin_tag_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function tagEdit(Request $request, Tag $tag, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TagType::class, $tag);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Genre modifié avec succès !');
            return $this->redirectToRoute('admin_tags');
        }

        return $this->render('admin/tags/form.html.twig', [
            'form' => $form->createView(),
            'title' => 'Modifier le Genre',
            'tag' => $tag
        ]);
    }

    #[Route('/tags/{id}/delete', name: 'admin_tag_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function tagDelete(Tag $tag, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si le tag est utilisé par des œuvres
        if ($tag->getOeuvres()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer ce genre car il est utilisé par ' . $tag->getOeuvres()->count() . ' œuvre(s).');
            return $this->redirectToRoute('admin_tags');
        }

        $entityManager->remove($tag);
        $entityManager->flush();

        $this->addFlash('success', 'Genre supprimé avec succès !');
        return $this->redirectToRoute('admin_tags');
    }

    #[Route('/sync-tags', name: 'app_sync_tags')]
    #[IsGranted('ROLE_ADMIN')]
    public function syncTags(MangaDxTagService $tagService): Response
    {
        try {
            $tags = $tagService->syncAllTags();
            $this->addFlash('success', count($tags) . ' genres synchronisés avec MangaDex !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la synchronisation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dashboard');
    }
} 