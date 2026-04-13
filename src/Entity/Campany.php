<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CampanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
], normalizationContext: ['groups' => ['campany:read']], denormalizationContext: ['groups' => ['campany:write']])]
#[ORM\Entity(repositoryClass: CampanyRepository::class)]
class Campany
{
    public const LEGAL_TYPE_PHYSICAL_PERSON = 'personne_physique';
    public const LEGAL_TYPE_LEGAL_ENTITY = 'personne_morale';

    #[Groups(['campany:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255)]
    private ?string $legalName = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255)]
    private ?string $sector = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255)]
    private ?string $adress = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255)]
    private ?string $siren = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $CreationDate = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255)]
    private ?string $Stage = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'campanies')]
    private Collection $customer;

    /**
     * @var Collection<int, FundingRequest>
     */
    #[ORM\OneToMany(targetEntity: FundingRequest::class, mappedBy: 'campany')]
    private Collection $fundingRequests;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    /**
     * @var Collection<int, Roadmap>
     */
    #[ORM\OneToMany(targetEntity: Roadmap::class, mappedBy: 'campany')]
    private Collection $roadmaps;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projetName = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $city = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $zipCode = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $country = null;

    #[Groups(['campany:read', 'campany:write'])]
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $legalType = null;

    public function __construct()
    {
        $this->customer = new ArrayCollection();
        $this->fundingRequests = new ArrayCollection();
        $this->roadmaps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): static
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(string $sector): static
    {
        $this->sector = $sector;

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

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(string $siren): static
    {
        $this->siren = trim($siren);
        $this->synchronizeLegalTypeFromSiren();

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->CreationDate;
    }

    public function setCreationDate(\DateTime $CreationDate): static
    {
        $this->CreationDate = $CreationDate;

        return $this;
    }

    public function getStage(): ?string
    {
        return $this->Stage;
    }

    public function setStage(string $Stage): static
    {
        $this->Stage = $Stage;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getCustomer(): Collection
    {
        return $this->customer;
    }

    public function addCustomer(User $customer): static
    {
        if (!$this->customer->contains($customer)) {
            $this->customer->add($customer);
        }

        return $this;
    }

    public function removeCustomer(User $customer): static
    {
        $this->customer->removeElement($customer);

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
            $fundingRequest->setCampany($this);
        }

        return $this;
    }

    public function removeFundingRequest(FundingRequest $fundingRequest): static
    {
        if ($this->fundingRequests->removeElement($fundingRequest)) {
            // set the owning side to null (unless already changed)
            if ($fundingRequest->getCampany() === $this) {
                $fundingRequest->setCampany(null);
            }
        }

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection<int, Roadmap>
     */
    public function getRoadmaps(): Collection
    {
        return $this->roadmaps;
    }

    public function addRoadmap(Roadmap $roadmap): static
    {
        if (!$this->roadmaps->contains($roadmap)) {
            $this->roadmaps->add($roadmap);
            $roadmap->setCampany($this);
        }

        return $this;
    }

    public function removeRoadmap(Roadmap $roadmap): static
    {
        if ($this->roadmaps->removeElement($roadmap)) {
            // set the owning side to null (unless already changed)
            if ($roadmap->getCampany() === $this) {
                $roadmap->setCampany(null);
            }
        }

        return $this;
    }

    public function getProjetName(): ?string
    {
        return $this->projetName;
    }

    public function setProjetName(?string $projetName): static
    {
        $this->projetName = $projetName;

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

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

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

    public function getLegalType(): ?string
    {
        return $this->legalType;
    }

    public function setLegalType(?string $legalType): static
    {
        $this->legalType = $legalType;

        return $this;
    }

    public function synchronizeLegalTypeFromSiren(): static
    {
        $siren = trim((string) $this->siren);

        if ($siren !== '' && preg_match('/^0+$/', $siren) === 1) {
            $this->legalType = self::LEGAL_TYPE_PHYSICAL_PERSON;
        } else {
            $this->legalType = self::LEGAL_TYPE_LEGAL_ENTITY;
        }

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public static function getLegalTypeChoices(): array
    {
        return [
            'Personne morale' => self::LEGAL_TYPE_LEGAL_ENTITY,
            'Personne physique' => self::LEGAL_TYPE_PHYSICAL_PERSON,
        ];
    }

}
