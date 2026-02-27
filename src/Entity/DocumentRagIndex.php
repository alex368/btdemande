<?php

namespace App\Entity;


use App\Repository\DocumentRagIndexRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentRagIndexRepository::class)]
#[ORM\Table(name: 'document_rag_index')]
class DocumentRagIndex
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    // ✅ 1 index par document
#[ORM\OneToOne(targetEntity: Document::class, inversedBy: 'documentRagIndex', cascade: ['persist'])]
#[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
private ?Document $document = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $contentHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        $this->document = $document;
        return $this;
    }

    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    public function setContentHash(string $hash): self
    {
        $this->contentHash = $hash;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $dt): self
    {
        $this->updatedAt = $dt;
        return $this;
    }
}