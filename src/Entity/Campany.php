<?php

namespace App\Entity;

use App\Repository\CampanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CampanyRepository::class)]
class Campany
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $legalName = null;

    #[ORM\Column(length: 255)]
    private ?string $sector = null;

    #[ORM\Column(length: 255)]
    private ?string $adress = null;

    #[ORM\Column(length: 255)]
    private ?string $siren = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $CreationDate = null;

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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    /**
     * @var Collection<int, Roadmap>
     */
    #[ORM\OneToMany(targetEntity: Roadmap::class, mappedBy: 'campany')]
    private Collection $roadmaps;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'campany')]
    private Collection $contact;

    public function __construct()
    {
        $this->customer = new ArrayCollection();
        $this->fundingRequests = new ArrayCollection();
        $this->roadmaps = new ArrayCollection();
        $this->contact = new ArrayCollection();
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

    public function setAdress(string $adress): static
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
        $this->siren = $siren;

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

    /**
     * @return Collection<int, Contact>
     */
    public function getContact(): Collection
    {
        return $this->contact;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contact->contains($contact)) {
            $this->contact->add($contact);
            $contact->setCampany($this);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        if ($this->contact->removeElement($contact)) {
            // set the owning side to null (unless already changed)
            if ($contact->getCampany() === $this) {
                $contact->setCampany(null);
            }
        }

        return $this;
    }

}
