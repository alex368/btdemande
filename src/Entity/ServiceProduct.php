<?php

namespace App\Entity;

use App\Repository\ServiceProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceProductRepository::class)]
class ServiceProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?float $price = null;

    /**
     * @var Collection<int, QuoteItem>
     */
    #[ORM\OneToMany(targetEntity: QuoteItem::class, mappedBy: 'productService')]
    private Collection $quoteItems;

    public function __construct()
    {
        $this->quoteItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, QuoteItem>
     */
    public function getQuoteItems(): Collection
    {
        return $this->quoteItems;
    }

    public function addQuoteItem(QuoteItem $quoteItem): static
    {
        if (!$this->quoteItems->contains($quoteItem)) {
            $this->quoteItems->add($quoteItem);
            $quoteItem->setProductService($this);
        }

        return $this;
    }

    public function removeQuoteItem(QuoteItem $quoteItem): static
    {
        if ($this->quoteItems->removeElement($quoteItem)) {
            // set the owning side to null (unless already changed)
            if ($quoteItem->getProductService() === $this) {
                $quoteItem->setProductService(null);
            }
        }

        return $this;
    }

}
