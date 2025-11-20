<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $productDescription = null;

    /**
     * @var Collection<int, DocumentTemplate>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: DocumentTemplate::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $documentTemplates;

    /**
     * @var Collection<int, FundingRequest>
     */
    #[ORM\OneToMany(targetEntity: FundingRequest::class, mappedBy: 'product', orphanRemoval: true)]
    private Collection $fundingRequests;

    /**
     * @var Collection<int, Roadmap>
     */
    #[ORM\OneToMany(targetEntity: Roadmap::class, mappedBy: 'product')]
    private Collection $roadmaps;

    public function __construct()
    {
        $this->documentTemplates = new ArrayCollection();
        $this->fundingRequests = new ArrayCollection();
        $this->roadmaps = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProductDescription(): ?string
    {
        return $this->productDescription;
    }

    public function setProductDescription(string $productDescription): static
    {
        $this->productDescription = $productDescription;

        return $this;
    }

    /**
     * @return Collection<int, DocumentTemplate>
     */
    public function getDocumentTemplates(): Collection
    {
        return $this->documentTemplates;
    }

    public function addDocumentTemplate(DocumentTemplate $documentTemplate): static
    {
        if (!$this->documentTemplates->contains($documentTemplate)) {
            $this->documentTemplates->add($documentTemplate);
            $documentTemplate->setProduct($this);
        }

        return $this;
    }

    public function removeDocumentTemplate(DocumentTemplate $documentTemplate): static
    {
        if ($this->documentTemplates->removeElement($documentTemplate)) {
            // set the owning side to null (unless already changed)
            if ($documentTemplate->getProduct() === $this) {
                $documentTemplate->setProduct(null);
            }
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
            $fundingRequest->setProduct($this);
        }

        return $this;
    }

    public function removeFundingRequest(FundingRequest $fundingRequest): static
    {
        if ($this->fundingRequests->removeElement($fundingRequest)) {
            // set the owning side to null (unless already changed)
            if ($fundingRequest->getProduct() === $this) {
                $fundingRequest->setProduct(null);
            }
        }

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
            $roadmap->setProduct($this);
        }

        return $this;
    }

    public function removeRoadmap(Roadmap $roadmap): static
    {
        if ($this->roadmaps->removeElement($roadmap)) {
            // set the owning side to null (unless already changed)
            if ($roadmap->getProduct() === $this) {
                $roadmap->setProduct(null);
            }
        }

        return $this;
    }
}
