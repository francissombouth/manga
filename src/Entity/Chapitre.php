<?php

namespace App\Entity;

use App\Repository\ChapitreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ChapitreRepository::class)]
class Chapitre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['chapitre:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['chapitre:read'])]
    private ?string $titre = null;

    #[ORM\Column]
    #[Groups(['chapitre:read'])]
    private ?int $ordre = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['chapitre:read'])]
    private ?string $resume = null;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(['chapitre:read'])]
    private array $pages = [];

    #[ORM\ManyToOne(inversedBy: 'chapitres')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['chapitre:read'])]
    private ?Oeuvre $oeuvre = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    private ?string $mangadxChapterId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getOrdre(): ?int
    {
        return $this->ordre;
    }

    public function setOrdre(int $ordre): static
    {
        $this->ordre = $ordre;
        return $this;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }

    public function setResume(?string $resume): static
    {
        $this->resume = $resume;
        return $this;
    }

    public function getPages(): array
    {
        return $this->pages;
    }

    public function setPages(array $pages): static
    {
        $this->pages = $pages;
        return $this;
    }

    public function getOeuvre(): ?Oeuvre
    {
        return $this->oeuvre;
    }

    public function setOeuvre(?Oeuvre $oeuvre): static
    {
        $this->oeuvre = $oeuvre;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNextChapitre(): ?self
    {
        if (!$this->oeuvre) {
            return null;
        }

        $nextOrdre = $this->ordre + 1;
        foreach ($this->oeuvre->getChapitres() as $chapitre) {
            if ($chapitre->getOrdre() === $nextOrdre) {
                return $chapitre;
            }
        }

        return null;
    }

    public function getPreviousChapitre(): ?self
    {
        if (!$this->oeuvre) {
            return null;
        }

        $previousOrdre = $this->ordre - 1;
        foreach ($this->oeuvre->getChapitres() as $chapitre) {
            if ($chapitre->getOrdre() === $previousOrdre) {
                return $chapitre;
            }
        }

        return null;
    }

    /**
     * Récupère les pages dynamiquement depuis l'API MangaDx (comme le catalogue)
     * Si l'œuvre a un mangadxId, on récupère les pages en temps réel
     */
    public function getPagesDynamiques(): array
    {
        // Si on a déjà des pages sauvegardées, on les retourne
        if (!empty($this->pages)) {
            return $this->pages;
        }

        // Si l'œuvre n'a pas d'ID MangaDx, on retourne un tableau vide
        if (!$this->oeuvre || !$this->oeuvre->getMangadxId()) {
            return [];
        }

        // On ne peut pas faire d'appel API directement depuis l'entité
        // Cette méthode sera utilisée par le service
        return [];
    }

    /**
     * Indique si ce chapitre peut récupérer des pages dynamiquement
     */
    public function peutRecupererPagesDynamiques(): bool
    {
        return $this->oeuvre && $this->oeuvre->getMangadxId() !== null;
    }

    public function getMangadxChapterId(): ?string
    {
        return $this->mangadxChapterId;
    }

    public function setMangadxChapterId(?string $mangadxChapterId): static
    {
        $this->mangadxChapterId = $mangadxChapterId;
        return $this;
    }
} 