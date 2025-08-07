<?php

namespace App\Service;

use App\Entity\Oeuvre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OeuvreImageService
{
    public function __construct(
        private ImgBBService $imgBBService,
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Upload une image de couverture pour une œuvre
     */
    public function uploadCoverForOeuvre(Oeuvre $oeuvre, UploadedFile $file): array
    {
        try {
            // Upload vers ImgBB
            $result = $this->imgBBService->uploadImage($file);
            
            if (!$result['success']) {
                return $result;
            }

            // Mettre à jour l'œuvre avec la nouvelle URL de couverture
            $oeuvre->setCouverture($result['url']);
            $this->entityManager->persist($oeuvre);
            $this->entityManager->flush();

            return [
                'success' => true,
                'url' => $result['url'],
                'oeuvre_id' => $oeuvre->getId(),
                'oeuvre_titre' => $oeuvre->getTitre(),
                'message' => 'Couverture mise à jour avec succès pour l\'œuvre "' . $oeuvre->getTitre() . '"'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la mise à jour de la couverture : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Upload plusieurs images de couverture pour différentes œuvres
     */
    public function uploadMultipleCovers(array $oeuvreFiles): array
    {
        $results = [];
        
        foreach ($oeuvreFiles as $oeuvreId => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $oeuvre = $this->entityManager->getRepository(Oeuvre::class)->find($oeuvreId);
            if (!$oeuvre) {
                $results[] = [
                    'success' => false,
                    'error' => 'Œuvre avec l\'ID ' . $oeuvreId . ' non trouvée'
                ];
                continue;
            }

            $results[] = $this->uploadCoverForOeuvre($oeuvre, $file);
        }

        return $results;
    }

    /**
     * Supprime la couverture d'une œuvre
     */
    public function removeCoverFromOeuvre(Oeuvre $oeuvre): array
    {
        try {
            $oldCoverUrl = $oeuvre->getCouverture();
            
            // Supprimer l'URL de la base de données
            $oeuvre->setCouverture(null);
            $this->entityManager->persist($oeuvre);
            $this->entityManager->flush();

            // Note: ImgBB ne permet pas de supprimer facilement les images
            // L'image restera sur ImgBB mais ne sera plus liée à l'œuvre

            return [
                'success' => true,
                'message' => 'Couverture supprimée avec succès',
                'old_url' => $oldCoverUrl
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Erreur lors de la suppression de la couverture : ' . $e->getMessage()
            ];
        }
    }

    /**
     * Récupère les informations sur les couvertures d'œuvres
     */
    public function getOeuvreCoverInfo(Oeuvre $oeuvre): array
    {
        return [
            'oeuvre_id' => $oeuvre->getId(),
            'oeuvre_titre' => $oeuvre->getTitre(),
            'has_cover' => !empty($oeuvre->getCouverture()),
            'cover_url' => $oeuvre->getCouverture(),
            'image_url' => $oeuvre->getImageUrl()
        ];
    }

    /**
     * Récupère toutes les œuvres sans couverture
     */
    public function getOeuvresWithoutCover(): array
    {
        return $this->entityManager->getRepository(Oeuvre::class)
            ->createQueryBuilder('o')
            ->where('o.couverture IS NULL OR o.couverture = :empty')
            ->setParameter('empty', '')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère toutes les œuvres avec couverture
     */
    public function getOeuvresWithCover(): array
    {
        return $this->entityManager->getRepository(Oeuvre::class)
            ->createQueryBuilder('o')
            ->where('o.couverture IS NOT NULL AND o.couverture != :empty')
            ->setParameter('empty', '')
            ->orderBy('o.titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Statistiques sur les couvertures
     */
    public function getCoverStatistics(): array
    {
        $totalOeuvres = $this->entityManager->getRepository(Oeuvre::class)->count([]);
        $oeuvresWithCover = count($this->getOeuvresWithCover());
        $oeuvresWithoutCover = count($this->getOeuvresWithoutCover());

        return [
            'total_oeuvres' => $totalOeuvres,
            'with_cover' => $oeuvresWithCover,
            'without_cover' => $oeuvresWithoutCover,
            'coverage_percentage' => $totalOeuvres > 0 ? round(($oeuvresWithCover / $totalOeuvres) * 100, 1) : 0
        ];
    }
} 