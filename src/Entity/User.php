<?php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $firstName = null;

    #[ORM\Column(length: 100)]
    private ?string $lastName = null;

#[ORM\Column(length: 191, unique: true)]
    private ?string $email = null;
    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $faceId = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private bool $verified = false;

    #[ORM\OneToMany(mappedBy: 'owner', targetEntity: Property::class)]
    private Collection $properties;

    #[ORM\OneToMany(mappedBy: 'tenant', targetEntity: Reservation::class)]
    private Collection $reservations;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $verificationToken = null;


    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->reservations = new ArrayCollection();
    }

    // Getters & Setters...
    public function getId(): ?int { return $this->id; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function setFirstName(string $firstName): self { $this->firstName = $firstName; return $this; }
    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(string $lastName): self { $this->lastName = $lastName; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function getFaceId(): ?string { return $this->faceId; }
    public function setFaceId(?string $faceId): self { $this->faceId = $faceId; return $this; }
    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }
    public function isVerified(): bool { return $this->verified; }
    public function setVerified(bool $verified): self { $this->verified = $verified; return $this; }
    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }
    public function getVerificationToken(): ?string { return $this->verificationToken; }
    public function setVerificationToken(?string $token): self { $this->verificationToken = $token; return $this; }


    public function getRoles(): array
{
    $roles = $this->roles;

    if (!is_array($roles)) {
        $roles = [];
    }

    if (!in_array('ROLE_USER', $roles, true)) {
        $roles[] = 'ROLE_USER';
    }

    return array_unique($roles);
}    public function getUserIdentifier(): string { return $this->email; }
    public function eraseCredentials(): void {}
  public function getProperties()
    {

        return $this->properties ?? new ArrayCollection();
    }
public function getReservations(): Collection
{
    return $this->reservations;
}


public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        return $this;
    }

    public function removeRole(string $role): self
    {
        $this->roles = array_filter($this->roles, fn($r) => $r !== $role);
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }
}