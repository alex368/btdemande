<?php

namespace App\Entity;

use App\Repository\DocumentRagChunkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRagChunkRepository::class)]
#[ORM\Table(name: 'document_rag_chunk')]
class DocumentRagChunk
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

#[ORM\ManyToOne(targetEntity: Document::class, inversedBy: 'ragChunks')]
#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
private ?Document $document = null;



    #[ORM\Column(type: 'integer')]
    private int $documentId;

    #[ORM\Column(type: 'integer')]
    private int $chunkIndex;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'json')]
    private array $embedding = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?int $pageNumber = null;

    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCourse(): ?Document
    {
        return $this->document;
    }

    public function setCourse(?Document $document): self
    {
        $this->document = $document;
        return $this;
    }

    public function getDocumentId(): int
    {
        return $this->documentId;
    }

    public function setDocumentId(int $documentId): self
    {
        $this->documentId = $documentId;
        return $this;
    }

    public function getChunkIndex(): int
    {
        return $this->chunkIndex;
    }

    public function setChunkIndex(int $chunkIndex): self
    {
        $this->chunkIndex = $chunkIndex;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getEmbedding(): array
    {
        return $this->embedding;
    }

    public function setEmbedding(array $embedding): self
    {
        $this->embedding = $embedding;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $dt): self
    {
        $this->createdAt = $dt;
        return $this;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(?int $pageNumber): static
    {
        $this->pageNumber = $pageNumber;

        return $this;
    }
}
