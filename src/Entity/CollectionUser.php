<?php

namespace App\Entity;

use App\Repository\CollectionUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CollectionUserRepository::class)]
class CollectionUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['collection:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['collection:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'collections')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['collection:read'])]
    private ?Oeuvre $oeuvre = null;

    #[ORM\Column]
    #[Groups(['collection:read'])]
    private ?\DateTimeImmutable $dateAjout = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['collection:read'])]
    private ?string $notePersonnelle = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->dateAjout = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
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

    public function getDateAjout(): ?\DateTimeImmutable
    {
        return $this->dateAjout;
    }

    public function setDateAjout(\DateTimeImmutable $dateAjout): static
    {
        $this->dateAjout = $dateAjout;
        return $this;
    }

    public function getNotePersonnelle(): ?string
    {
        return $this->notePersonnelle;
    }

    public function setNotePersonnelle(?string $notePersonnelle): static
    {
        $this->notePersonnelle = $notePersonnelle;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
} 