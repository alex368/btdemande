<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\DocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
], normalizationContext: ['groups' => ['document:read']], denormalizationContext: ['groups' => ['document:write']])]
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document
{
    #[Groups(['document:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?DocumentTemplate $DocumentDefinition = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\ManyToOne(inversedBy: 'documents')]
    private ?FundingRequest $fundingRequest = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column]
    private ?bool $status = null;

    #[Groups(['document:read', 'document:write'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $Comment = null;


    #[ORM\OneToOne(mappedBy: 'document', targetEntity: DocumentRagIndex::class, cascade: ['persist', 'remove'])]
private ?DocumentRagIndex $documentRagIndex = null;

#[ORM\OneToMany(mappedBy: 'document', targetEntity: DocumentRagChunk::class, cascade: ['persist', 'remove'])]
private Collection $ragChunks;


public function __construct()
{
    $this->ragChunks = new ArrayCollection();
}


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

    public function getDocumentRagIndex(): ?DocumentRagIndex
{
    return $this->documentRagIndex;
}

public function setDocumentRagIndex(?DocumentRagIndex $index): self
{
    $this->documentRagIndex = $index;
    if ($index !== null && $index->getDocument() !== $this) {
        $index->setDocument($this);
    }
    return $this;
}

public function getRagChunks(): Collection
{
    return $this->ragChunks;
}


}
