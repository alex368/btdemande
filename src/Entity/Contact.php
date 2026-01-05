<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $salutation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $email = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $phone = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $mobilePhone = null;

    /**
     * @var Collection<int, Opportunity>
     */
    #[ORM\OneToMany(targetEntity: Opportunity::class, mappedBy: 'contact')]
    private Collection $opportunity;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'contact')]
    private Collection $activities;

    #[ORM\ManyToOne(inversedBy: 'contact')]
    private ?Campany $campany = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $socialMedia = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $occupation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zipCode = null;

    /**
     * @var Collection<int, Quote>
     */
    #[ORM\OneToMany(targetEntity: Quote::class, mappedBy: 'customer')]
    private Collection $quotes;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    private ?User $account = null;

    public function __construct()
    {
        $this->opportunity = new ArrayCollection();
        $this->activities = new ArrayCollection();
        $this->quotes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function setSalutation(string $salutation): static
    {
        $this->salutation = $salutation;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?array
    {
        return $this->email;
    }

    public function setEmail(?array $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?array
    {
        return $this->phone;
    }

    public function setPhone(?array $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getMobilePhone(): ?array
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?array $mobilePhone): static
    {
        $this->mobilePhone = $mobilePhone;

        return $this;
    }

    /**
     * @return Collection<int, Opportunity>
     */
    public function getOpportunity(): Collection
    {
        return $this->opportunity;
    }

    public function addOpportunity(Opportunity $opportunity): static
    {
        if (!$this->opportunity->contains($opportunity)) {
            $this->opportunity->add($opportunity);
            $opportunity->setContact($this);
        }

        return $this;
    }

    public function removeOpportunity(Opportunity $opportunity): static
    {
        if ($this->opportunity->removeElement($opportunity)) {
            // set the owning side to null (unless already changed)
            if ($opportunity->getContact() === $this) {
                $opportunity->setContact(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
            $activity->setContact($this);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        if ($this->activities->removeElement($activity)) {
            // set the owning side to null (unless already changed)
            if ($activity->getContact() === $this) {
                $activity->setContact(null);
            }
        }

        return $this;
    }

    public function getCampany(): ?Campany
    {
        return $this->campany;
    }

    public function setCampany(?Campany $campany): static
    {
        $this->campany = $campany;

        return $this;
    }

    public function getSocialMedia(): ?array
    {
        return $this->socialMedia;
    }

    public function setSocialMedia(?array $socialMedia): static
    {
        $this->socialMedia = $socialMedia;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(?string $occupation): static
    {
        $this->occupation = $occupation;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return Collection<int, Quote>
     */
    public function getQuotes(): Collection
    {
        return $this->quotes;
    }

    public function addQuote(Quote $quote): static
    {
        if (!$this->quotes->contains($quote)) {
            $this->quotes->add($quote);
            $quote->setCustomer($this);
        }

        return $this;
    }

    public function removeQuote(Quote $quote): static
    {
        if ($this->quotes->removeElement($quote)) {
            // set the owning side to null (unless already changed)
            if ($quote->getCustomer() === $this) {
                $quote->setCustomer(null);
            }
        }

        return $this;
    }



public function getAccount(): ?User
{
    return $this->account;
}

public function setAccount(?User $account): static
{
    $this->account = $account;

    return $this;
}


public function toUser(): User
{
    $user = new User();

    $user->setName($this->lastName ?? '');
    $user->setLastName($this->firstName ?? '');

    // Email
    if (!empty($this->email) && is_array($this->email)) {
        $user->setEmail($this->email[0]);
    }

    // Username
    if (method_exists($user, 'setUsername')) {
        $username = strtolower(trim(($this->lastName ?? '') . '.' . ($this->firstName ?? '')));
        $user->setUsername($username ?: uniqid('user_'));
    }

    //     if (method_exists($user, 'setAccountId')) {
    //     $user->setAccountId($this->getId());
    // }

    // Numéro sécurisé
    $number = $this->getPrimaryPhone() ?? "0000000000";
    $user->setNumber($number);

    // Rôle par défaut
    $user->setRoles(['ROLE_CUSTOMER']);

    // Mot de passe temporaire
    $user->setPassword('TEMPORARY');

    return $user;
}


public function getPrimaryPhone(): ?string
{
    // mobilePhone prioritaire
    if (is_array($this->mobilePhone) && !empty($this->mobilePhone)) {
        return $this->mobilePhone[0];
    }

    // fallback téléphone fixe
    if (is_array($this->phone) && !empty($this->phone)) {
        return $this->phone[0];
    }

    return null;
}

    
}
