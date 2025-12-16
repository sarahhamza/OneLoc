<?php
namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'reservations')]
class Reservation
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $checkIn = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $checkOut = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

#[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $status = 'pending'; // pending, accepted, rejected

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $tenant = null;

    #[ORM\ManyToOne(targetEntity: Property::class, inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Property $property = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = 'pending';
    }

    // --- Getters & Setters ---
    public function getId(): ?int { return $this->id; }

    public function getCheckIn(): ?\DateTimeInterface { return $this->checkIn; }
    public function setCheckIn(\DateTimeInterface $checkIn): self { $this->checkIn = $checkIn; return $this; }

    public function getCheckOut(): ?\DateTimeInterface { return $this->checkOut; }
    public function setCheckOut(\DateTimeInterface $checkOut): self { $this->checkOut = $checkOut; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self
    {
        if (!in_array($status, ['pending', 'accepted', 'rejected'])) {
            throw new \InvalidArgumentException("Invalid status");
        }
        $this->status = $status;
        return $this;
    }

    public function getTenant(): ?User { return $this->tenant; }
    public function setTenant(?User $tenant): self { $this->tenant = $tenant; return $this; }

    public function getProperty(): ?Property { return $this->property; }
    public function setProperty(?Property $property): self { $this->property = $property; return $this; }
}