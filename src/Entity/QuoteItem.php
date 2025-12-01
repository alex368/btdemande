<?php

namespace App\Entity;

use App\Repository\QuoteItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteItemRepository::class)]
class QuoteItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'quoteItems')]
    private ?Quote $quote = null;



    #[ORM\ManyToOne(inversedBy: 'quoteItems')]
    private ?ServiceProduct $productService = null;

    /**
     * @var Collection<int, AddOnProduct>
     */
    #[ORM\OneToMany(targetEntity: AddOnProduct::class, mappedBy: 'quoteItem', cascade: ['persist','remove'])]
    private Collection $addOnProducts;




    public function __construct()
    {
        $this->addOnProducts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

  
 

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }

    public function setQuote(?Quote $quote): static
    {
        $this->quote = $quote;

        return $this;
    }


    public function getProductService(): ?ServiceProduct
    {
        return $this->productService;
    }

    public function setProductService(?ServiceProduct $productService): static
    {
        $this->productService = $productService;

        return $this;
    }

    /**
     * @return Collection<int, AddOnProduct>
     */
    public function getAddOnProducts(): Collection
    {
        return $this->addOnProducts;
    }

    public function addAddOnProduct(AddOnProduct $addOnProduct): static
    {
        if (!$this->addOnProducts->contains($addOnProduct)) {
            $this->addOnProducts->add($addOnProduct);
            $addOnProduct->setQuoteItem($this);
        }

        return $this;
    }

    public function removeAddOnProduct(AddOnProduct $addOnProduct): static
    {
        if ($this->addOnProducts->removeElement($addOnProduct)) {
            // set the owning side to null (unless already changed)
            if ($addOnProduct->getQuoteItem() === $this) {
                $addOnProduct->setQuoteItem(null);
            }
        }

        return $this;
    }





    
}
