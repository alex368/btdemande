<?php

namespace App\Entity;

use App\Repository\QuoteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuoteRepository::class)]
class Quote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255)]
    private ?string $quoteNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $expirationDate = null;

    #[ORM\ManyToOne(inversedBy: 'quotes')]
    private ?Contact $customer = null;

    #[ORM\OneToMany(mappedBy: 'quote', targetEntity: QuoteItem::class, cascade: ['persist','remove'], orphanRemoval: true)]
    private Collection $quoteItems;


    public function __construct()
    {
           $this->quoteItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getQuoteNumber(): ?string
    {
        return $this->quoteNumber;
    }

    public function setQuoteNumber(string $quoteNumber): static
    {
        $this->quoteNumber = $quoteNumber;

        return $this;
    }

    public function getExpirationDate(): ?\DateTime
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(\DateTime $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getCustomer(): ?Contact
    {
        return $this->customer;
    }

    public function setCustomer(?Contact $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

   public function getQuoteItems(): Collection
{
    return $this->quoteItems;
}

public function addQuoteItem(QuoteItem $item): static
{
    if (!$this->quoteItems->contains($item)) {
        $this->quoteItems->add($item);
        $item->setQuote($this);
    }
    return $this;
}

public function removeQuoteItem(QuoteItem $item): static
{
    if ($this->quoteItems->removeElement($item)) {
        if ($item->getQuote() === $this) {
            $item->setQuote(null);
        }
    }
    return $this;
}



}
