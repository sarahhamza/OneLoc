<?php
namespace App\Entity;

use App\Repository\PropertyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PropertyRepository::class)]
#[ORM\Table(name: 'properties')]
class Property
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: 'integer')]
    private ?int $bedrooms = null;

    #[ORM\Column(type: 'integer')]
    private ?int $bathrooms = null;

    #[ORM\Column(type: 'integer')]
    private ?int $area = null;

    #[ORM\Column]
    private bool $available = true;

    #[ORM\Column(type: 'json')]
    private array $photos = [];

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    #[ORM\OneToMany(mappedBy: 'property', targetEntity: Reservation::class)]
    private Collection $reservations;

  #[ORM\Column(type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    // Getters & Setters...
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(string $address): self { $this->address = $address; return $this; }
    public function getPrice(): ?string { return $this->price; }
    public function setPrice(string $price): self { $this->price = $price; return $this; }
    public function getBedrooms(): ?int { return $this->bedrooms; }
    public function setBedrooms(int $bedrooms): self { $this->bedrooms = $bedrooms; return $this; }
    public function getBathrooms(): ?int { return $this->bathrooms; }
    public function setBathrooms(int $bathrooms): self { $this->bathrooms = $bathrooms; return $this; }
    public function getArea(): ?int { return $this->area; }
    public function setArea(int $area): self { $this->area = $area; return $this; }
    public function isAvailable(): bool { return $this->available; }
    public function setAvailable(bool $available): self { $this->available = $available; return $this; }
    public function getPhotos(): array { return $this->photos; }
    public function setPhotos(array $photos): self { $this->photos = $photos; return $this; }
    public function addPhoto(string $path): self { if (!in_array($path, $this->photos)) $this->photos[] = $path; return $this; }
    public function getOwner(): ?User { return $this->owner; }
    public function setOwner(?User $owner): self { $this->owner = $owner; return $this; }
    public function getReservations(): Collection { return $this->reservations; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
}