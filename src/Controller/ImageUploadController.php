<?php

namespace App\Controller;

use App\Service\ImgBBService;
use App\Service\OeuvreImageService;
use App\Entity\Oeuvre;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/admin/images')]
#[IsGranted('ROLE_ADMIN')]
class ImageUploadController extends AbstractController
{
    public function __construct(
        private ImgBBService $imgBBService,
        private OeuvreImageService $oeuvreImageService,
        private ParameterBagInterface $params
    ) {}

    #[Route('/upload', name: 'admin_image_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFiles = $request->files->get('images', []);
            
            if (empty($uploadedFiles)) {
                $this->addFlash('error', 'Aucun fichier sélectionné.');
                return $this->redirectToRoute('admin_image_upload');
            }

            $results = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($uploadedFiles as $file) {
                if ($file) {
                    $result = $this->imgBBService->uploadImage($file);
                    $results[] = $result;
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorCount++;
                    }
                }
            }

            // Stocker les résultats en session pour l'affichage
            $request->getSession()->set('upload_results', $results);

            if ($successCount > 0) {
                $this->addFlash('success', "✅ {$successCount} image(s) uploadée(s) avec succès !");
            }
            
            if ($errorCount > 0) {
                $this->addFlash('error', "❌ {$errorCount} image(s) n'ont pas pu être uploadée(s).");
            }

            return $this->redirectToRoute('admin_image_upload');
        }

        // Récupérer les résultats de l'upload précédent
        $uploadResults = $request->getSession()->get('upload_results', []);
        $request->getSession()->remove('upload_results');

        return $this->render('admin/image_upload.html.twig', [
            'upload_results' => $uploadResults
        ]);
    }

    #[Route('/upload-ajax', name: 'admin_image_upload_ajax', methods: ['POST'])]
    public function uploadAjax(Request $request): JsonResponse
    {
        $uploadedFiles = $request->files->get('images', []);
        
        if (empty($uploadedFiles)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucun fichier sélectionné.'
            ]);
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($uploadedFiles as $file) {
            if ($file) {
                $result = $this->imgBBService->uploadImage($file);
                $results[] = $result;
                
                if ($result['success']) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }
        }

        return new JsonResponse([
            'success' => true,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'success' => $successCount,
                'errors' => $errorCount
            ]
        ]);
    }

    #[Route('/delete', name: 'admin_image_delete', methods: ['POST'])]
    public function delete(Request $request): JsonResponse
    {
        $deleteUrl = $request->request->get('delete_url');
        
        if (!$deleteUrl) {
            return new JsonResponse([
                'success' => false,
                'error' => 'URL de suppression manquante.'
            ]);
        }

        $success = $this->imgBBService->deleteImage($deleteUrl);

        return new JsonResponse([
            'success' => $success,
            'message' => $success ? 'Image supprimée avec succès.' : 'Erreur lors de la suppression.'
        ]);
    }

    #[Route('/covers', name: 'admin_image_covers', methods: ['GET'])]
    public function covers(): Response
    {
        $oeuvresWithoutCover = $this->oeuvreImageService->getOeuvresWithoutCover();
        $oeuvresWithCover = $this->oeuvreImageService->getOeuvresWithCover();
        $coverStats = $this->oeuvreImageService->getCoverStatistics();

        return $this->render('admin/image_covers.html.twig', [
            'oeuvres_without_cover' => $oeuvresWithoutCover,
            'oeuvres_with_cover' => $oeuvresWithCover,
            'cover_stats' => $coverStats
        ]);
    }

    #[Route('/upload-cover/{id}', name: 'admin_upload_cover', methods: ['POST'])]
    public function uploadCover(Request $request, Oeuvre $oeuvre): JsonResponse
    {
        $file = $request->files->get('cover');
        
        if (!$file) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Aucun fichier sélectionné.'
            ]);
        }

        $result = $this->oeuvreImageService->uploadCoverForOeuvre($oeuvre, $file);

        return new JsonResponse($result);
    }

    #[Route('/remove-cover/{id}', name: 'admin_remove_cover', methods: ['POST'])]
    public function removeCover(Oeuvre $oeuvre): JsonResponse
    {
        $result = $this->oeuvreImageService->removeCoverFromOeuvre($oeuvre);

        return new JsonResponse($result);
    }

    #[Route('/covers-stats', name: 'admin_covers_stats', methods: ['GET'])]
    public function coversStats(): JsonResponse
    {
        $stats = $this->oeuvreImageService->getCoverStatistics();

        return new JsonResponse([
            'success' => true,
            'stats' => $stats
        ]);
    }
} 