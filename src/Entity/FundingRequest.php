<?php

namespace App\Entity;

use App\Repository\FundingRequestRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: FundingRequestRepository::class)]
class FundingRequest
{
    public const STATUS_IN_PROGRESS = 'En cours';
    public const STATUS_WAITING_CLIENT = 'Attente client';
    public const STATUS_BACK_FROM_CLIENT = 'Retour client';
    public const STATUS_PROCESSING = 'Traitement du dossier';
    public const STATUS_WAITING_ACCOUNT_MANAGER = "Attente chargé d'affaires";
    public const STATUS_CLOSED = 'Dossier clôturé';
    public const STATUS_VALIDATED = 'Validé';

    public const DECISION_VALIDATED = 'Validé';
    public const DECISION_REFUSED = 'Refusé';

    private const STATUS_ALIASES = [
        'en cours' => self::STATUS_IN_PROGRESS,
        'attente client' => self::STATUS_WAITING_CLIENT,
        'retour client' => self::STATUS_BACK_FROM_CLIENT,
        'traitement du dossier' => self::STATUS_PROCESSING,
        "attente chargé d'affaires" => self::STATUS_WAITING_ACCOUNT_MANAGER,
        'dossier clôturé' => self::STATUS_CLOSED,
        'dossier cloturé' => self::STATUS_CLOSED,
        'validé' => self::STATUS_VALIDATED,
        'valide' => self::STATUS_VALIDATED,
    ];

    private const DECISION_ALIASES = [
        'validé' => self::DECISION_VALIDATED,
        'valide' => self::DECISION_VALIDATED,
        'validée' => self::DECISION_VALIDATED,
        'refusé' => self::DECISION_REFUSED,
        'refuse' => self::DECISION_REFUSED,
        'refusée' => self::DECISION_REFUSED,
    ];

    #[Groups(['document:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fundingRequests')]
    private ?Campany $campany = null;

    #[Groups(['document:read'])]
    #[ORM\Column]
    private ?int $amount = null;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'fundingRequest')]
    private Collection $documents;

    #[ORM\ManyToOne(inversedBy: 'fundingRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[Groups(['document:read'])]
    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'fundingRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    private ?User $assistant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $decision = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampany(): ?Campany
    {
        return $this->campany;
    }

    public function setCampany(?Campany $campany): static
    {
        $this->campany = $campany;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setFundingRequest($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getFundingRequest() === $this) {
                $document->setFundingRequest(null);
            }
        }

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = self::normalizeStatus($status);

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAssistant(): ?User
    {
        return $this->assistant;
    }

    public function setAssistant(?User $assistant): static
    {
        $this->assistant = $assistant;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(?string $decision): static
    {
        $this->decision = self::normalizeDecision($decision);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Statuts visibles dans le suivi client.
     *
     * @return list<string>
     */
    public static function getTrackingStatuses(): array
    {
        return [
            self::STATUS_IN_PROGRESS,
            self::STATUS_WAITING_CLIENT,
            self::STATUS_BACK_FROM_CLIENT,
            self::STATUS_PROCESSING,
            self::STATUS_WAITING_ACCOUNT_MANAGER,
            self::STATUS_CLOSED,
        ];
    }

    /**
     * Statuts de fin de traitement (back-office).
     *
     * @return list<string>
     */
    public static function getBackOfficeStatusChoices(): array
    {
        return [
            self::STATUS_WAITING_ACCOUNT_MANAGER,
            self::STATUS_CLOSED,
        ];
    }

    public static function normalizeStatus(string $status): string
    {
        $normalized = trim($status);
        $key = mb_strtolower($normalized);

        return self::STATUS_ALIASES[$key] ?? $normalized;
    }

    public static function normalizeDecision(?string $decision): ?string
    {
        if ($decision === null) {
            return null;
        }

        $normalized = trim($decision);
        if ($normalized === '') {
            return null;
        }

        $key = mb_strtolower($normalized);

        return self::DECISION_ALIASES[$key] ?? $normalized;
    }

    public function getDisplayStatus(): ?string
    {
        if ($this->status === null) {
            return null;
        }

        if (
            self::normalizeStatus($this->status) === self::STATUS_CLOSED
            && self::normalizeDecision($this->decision) === self::DECISION_VALIDATED
        ) {
            return self::STATUS_VALIDATED;
        }

        return $this->status;
    }

    public function getTrackingProgressIndex(): int
    {
        if ($this->status === null) {
            return -1;
        }

        $status = self::normalizeStatus($this->status);
        if ($status === self::STATUS_VALIDATED) {
            $status = self::STATUS_CLOSED;
        }

        $index = array_search($status, self::getTrackingStatuses(), true);

        return $index === false ? -1 : $index;
    }
}
