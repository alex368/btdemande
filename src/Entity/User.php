<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $number = null;

    /**
     * @var Collection<int, Campany>
     */
    #[ORM\ManyToMany(targetEntity: Campany::class, mappedBy: 'customer')]
    private Collection $campanies;

    /**
     * @var Collection<int, FundingRequest>
     */
    #[ORM\OneToMany(targetEntity: FundingRequest::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $fundingRequests;

    /**
     * @var Collection<int, Opportunity>
     */
    #[ORM\OneToMany(targetEntity: Opportunity::class, mappedBy: 'user')]
    private Collection $opportunities;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'account')]
    private Collection $contacts;

    /**
     * @var Collection<int, ResetPassword>
     */
    #[ORM\OneToMany(targetEntity: ResetPassword::class, mappedBy: 'user')]
    private Collection $resetPasswords;


    public function __construct()
    {
        $this->campanies = new ArrayCollection();
        $this->fundingRequests = new ArrayCollection();
        $this->opportunities = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        $this->resetPasswords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(string $number): static
    {
        $this->number = $number;

        return $this;
    }

    /**
     * @return Collection<int, Campany>
     */
    public function getCampanies(): Collection
    {
        return $this->campanies;
    }

    public function addCampany(Campany $campany): static
    {
        if (!$this->campanies->contains($campany)) {
            $this->campanies->add($campany);
            $campany->addCustomer($this);
        }

        return $this;
    }

    public function removeCampany(Campany $campany): static
    {
        if ($this->campanies->removeElement($campany)) {
            $campany->removeCustomer($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, FundingRequest>
     */
    public function getFundingRequests(): Collection
    {
        return $this->fundingRequests;
    }

    public function addFundingRequest(FundingRequest $fundingRequest): static
    {
        if (!$this->fundingRequests->contains($fundingRequest)) {
            $this->fundingRequests->add($fundingRequest);
            $fundingRequest->setUser($this);
        }

        return $this;
    }

    public function removeFundingRequest(FundingRequest $fundingRequest): static
    {
        if ($this->fundingRequests->removeElement($fundingRequest)) {
            // set the owning side to null (unless already changed)
            if ($fundingRequest->getUser() === $this) {
                $fundingRequest->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Opportunity>
     */
    public function getOpportunities(): Collection
    {
        return $this->opportunities;
    }

    public function addOpportunity(Opportunity $opportunity): static
    {
        if (!$this->opportunities->contains($opportunity)) {
            $this->opportunities->add($opportunity);
            $opportunity->setUser($this);
        }

        return $this;
    }

    public function removeOpportunity(Opportunity $opportunity): static
    {
        if ($this->opportunities->removeElement($opportunity)) {
            // set the owning side to null (unless already changed)
            if ($opportunity->getUser() === $this) {
                $opportunity->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
            $contact->setAccount($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contacts->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getAccount() === $this) {
                $contact->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ResetPassword>
     */
    public function getResetPasswords(): Collection
    {
        return $this->resetPasswords;
    }

    public function addResetPassword(ResetPassword $resetPassword): static
    {
        if (!$this->resetPasswords->contains($resetPassword)) {
            $this->resetPasswords->add($resetPassword);
            $resetPassword->setUser($this);
        }

        return $this;
    }

    public function removeResetPassword(ResetPassword $resetPassword): static
    {
        if ($this->resetPasswords->removeElement($resetPassword)) {
            // set the owning side to null (unless already changed)
            if ($resetPassword->getUser() === $this) {
                $resetPassword->setUser(null);
            }
        }

        return $this;
    }


   



}
