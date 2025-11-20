<?php

namespace App\Entity;

use App\Repository\DocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?DocumentTemplate $DocumentDefinition = null;

    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?FundingRequest $fundingRequest = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $Comment = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getDocumentDefinition(): ?DocumentTemplate
    {
        return $this->DocumentDefinition;
    }

    public function setDocumentDefinition(?DocumentTemplate $DocumentDefinition): static
    {
        $this->DocumentDefinition = $DocumentDefinition;

        return $this;
    }

    public function getFundingRequest(): ?FundingRequest
    {
        return $this->fundingRequest;
    }

    public function setFundingRequest(?FundingRequest $fundingRequest): static
    {
        $this->fundingRequest = $fundingRequest;

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

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->Comment;
    }

    public function setComment(?string $Comment): static
    {
        $this->Comment = $Comment;

        return $this;
    }
}
