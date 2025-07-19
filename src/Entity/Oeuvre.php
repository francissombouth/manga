<?php

namespace App\Entity;

use App\Repository\OeuvreRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OeuvreRepository::class)]
class Oeuvre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['oeuvre:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['oeuvre:read'])]
    #[Assert\NotBlank(message: 'Le titre est obligatoire')]
    #[Assert\Length(min: 1, max: 255, minMessage: 'Le titre doit contenir au moins {{ limit }} caractère', maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères')]
    private ?string $titre = null;

    #[ORM\ManyToOne(inversedBy: 'oeuvres')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['oeuvre:read'])]
    #[Assert\NotNull(message: 'L\'auteur est obligatoire')]
    private ?Auteur $auteur = null;

    #[ORM\Column(length: 50)]
    #[Groups(['oeuvre:read'])]
    #[Assert\NotBlank(message: 'Le type est obligatoire')]
    #[Assert\Choice(choices: ['Manga', 'Manhwa', 'Manhua', 'Light Novel', 'Web Novel'], message: 'Le type doit être l\'un des suivants : {{ choices }}')]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['oeuvre:read'])]
    #[Assert\Url(protocols: ['http', 'https'], message: "L'URL de la couverture doit être une URL valide commençant par http:// ou https://")]
    #[Assert\NotBlank(allowNull: true, message: "L'URL ne peut pas être une chaîne invalide")]
    private ?string $couverture = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $resume = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?\DateTimeInterface $datePublication = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $isbn = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $mangadxId = null;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $statut = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $originalLanguage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $demographic = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $contentRating = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?array $alternativeTitles = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $lastVolume = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?string $lastChapter = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['oeuvre:read'])]
    private ?int $year = null;

    #[ORM\OneToMany(mappedBy: 'oeuvre', targetEntity: Chapitre::class, orphanRemoval: true)]
    private Collection $chapitres;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'oeuvres')]
    private Collection $tags;

    #[ORM\OneToMany(mappedBy: 'oeuvre', targetEntity: CollectionUser::class, orphanRemoval: true)]
    private Collection $collections;

    #[ORM\OneToMany(mappedBy: 'oeuvre', targetEntity: Statut::class, orphanRemoval: true)]
    private Collection $statuts;

    #[ORM\OneToMany(mappedBy: 'oeuvre', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: 'oeuvre', targetEntity: OeuvreNote::class, orphanRemoval: true)]
    private Collection $notes;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->chapitres = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->collections = new ArrayCollection();
        $this->statuts = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->notes = new ArrayCollection();
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

    public function getAuteur(): ?Auteur
    {
        return $this->auteur;
    }

    public function setAuteur(?Auteur $auteur): static
    {
        $this->auteur = $auteur;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getCouverture(): ?string
    {
        return $this->couverture;
    }

    public function setCouverture(?string $couverture): static
    {
        $this->couverture = $couverture;
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

    public function getDatePublication(): ?\DateTimeInterface
    {
        return $this->datePublication;
    }

    public function setDatePublication(?\DateTimeInterface $datePublication): static
    {
        $this->datePublication = $datePublication;
        return $this;
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getChapitres(): Collection
    {
        return $this->chapitres;
    }

    /**
     * Retourne les chapitres triés par ordre croissant
     */
    public function getChapitresSorted(): array
    {
        $chapitres = $this->chapitres->toArray();
        usort($chapitres, fn($a, $b) => $a->getOrdre() <=> $b->getOrdre());
        return $chapitres;
    }

    /**
     * Retourne le premier chapitre (ordre le plus bas)
     */
    public function getFirstChapter(): ?Chapitre
    {
        $chapitres = $this->getChapitresSorted();
        return !empty($chapitres) ? $chapitres[0] : null;
    }

    /**
     * Retourne le dernier chapitre (ordre le plus élevé)
     */
    public function getLatestChapter(): ?Chapitre
    {
        $chapitres = $this->getChapitresSorted();
        return !empty($chapitres) ? end($chapitres) : null;
    }

    public function addChapitre(Chapitre $chapitre): static
    {
        if (!$this->chapitres->contains($chapitre)) {
            $this->chapitres->add($chapitre);
            $chapitre->setOeuvre($this);
        }
        return $this;
    }

    public function removeChapitre(Chapitre $chapitre): static
    {
        if ($this->chapitres->removeElement($chapitre)) {
            if ($chapitre->getOeuvre() === $this) {
                $chapitre->setOeuvre(null);
            }
        }
        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }

    public function getCollections(): Collection
    {
        return $this->collections;
    }

    public function addCollection(CollectionUser $collection): static
    {
        if (!$this->collections->contains($collection)) {
            $this->collections->add($collection);
            $collection->setOeuvre($this);
        }
        return $this;
    }

    public function removeCollection(CollectionUser $collection): static
    {
        if ($this->collections->removeElement($collection)) {
            if ($collection->getOeuvre() === $this) {
                $collection->setOeuvre(null);
            }
        }
        return $this;
    }

    public function getStatuts(): Collection
    {
        return $this->statuts;
    }

    public function addStatut(Statut $statut): static
    {
        if (!$this->statuts->contains($statut)) {
            $this->statuts->add($statut);
            $statut->setOeuvre($this);
        }
        return $this;
    }

    public function removeStatut(Statut $statut): static
    {
        if ($this->statuts->removeElement($statut)) {
            if ($statut->getOeuvre() === $this) {
                $statut->setOeuvre(null);
            }
        }
        return $this;
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): self
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires[] = $commentaire;
            $commentaire->setOeuvre($this);
        }
        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): self
    {
        if ($this->commentaires->removeElement($commentaire)) {
            if ($commentaire->getOeuvre() === $this) {
                $commentaire->setOeuvre(null);
            }
        }
        return $this;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(OeuvreNote $note): self
    {
        if (!$this->notes->contains($note)) {
            $this->notes[] = $note;
            $note->setOeuvre($this);
        }
        return $this;
    }

    public function removeNote(OeuvreNote $note): self
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getOeuvre() === $this) {
                $note->setOeuvre(null);
            }
        }
        return $this;
    }

    public function getAverageNote(): ?float
    {
        if ($this->notes->isEmpty()) {
            return null;
        }

        $total = 0;
        foreach ($this->notes as $note) {
            $total += $note->getNote();
        }

        return round($total / $this->notes->count(), 2);
    }

    public function getUserNote(?User $user): ?OeuvreNote
    {
        if (!$user) {
            return null;
        }

        foreach ($this->notes as $note) {
            if ($note->getUser() === $user) {
                return $note;
            }
        }
        return null;
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

    public function getMangadxId(): ?string
    {
        return $this->mangadxId;
    }

    public function setMangadxId(?string $mangadxId): static
    {
        $this->mangadxId = $mangadxId;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): static
    {
        $this->statut = $statut;
        return $this;
    }

    public function getOriginalLanguage(): ?string
    {
        return $this->originalLanguage;
    }

    public function setOriginalLanguage(?string $originalLanguage): static
    {
        $this->originalLanguage = $originalLanguage;
        return $this;
    }

    public function getDemographic(): ?string
    {
        return $this->demographic;
    }

    public function setDemographic(?string $demographic): static
    {
        $this->demographic = $demographic;
        return $this;
    }

    public function getContentRating(): ?string
    {
        return $this->contentRating;
    }

    public function setContentRating(?string $contentRating): static
    {
        $this->contentRating = $contentRating;
        return $this;
    }

    public function getAlternativeTitles(): ?array
    {
        return $this->alternativeTitles;
    }

    public function setAlternativeTitles(?array $alternativeTitles): static
    {
        $this->alternativeTitles = $alternativeTitles;
        return $this;
    }

    public function getLastVolume(): ?string
    {
        return $this->lastVolume;
    }

    public function setLastVolume(?string $lastVolume): static
    {
        $this->lastVolume = $lastVolume;
        return $this;
    }

    public function getLastChapter(): ?string
    {
        return $this->lastChapter;
    }

    public function setLastChapter(?string $lastChapter): static
    {
        $this->lastChapter = $lastChapter;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;
        return $this;
    }

    /**
     * Retourne l'URL de l'image de couverture ou un placeholder
     */
    #[Groups(['oeuvre:read'])]
    public function getImageUrl(): string
    {
        if ($this->couverture && !empty($this->couverture)) {
            return $this->couverture;
        }
        
        return '/images/placeholder-book.jpg';
    }
} 