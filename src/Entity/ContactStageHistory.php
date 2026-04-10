<?php

namespace App\Entity;

use App\Repository\ContactStageHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactStageHistoryRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_contact_stage', columns: ['contact_id', 'stage'])]
class ContactStageHistory
{
    public const STAGE_PROSPECT = 'prospect';
    public const STAGE_QUALIFICATION = 'qualification';
    public const STAGE_PROPOSITION = 'proposition';
    public const STAGE_NEGOTIATION = 'negociation';
    public const STAGE_WON = 'gagne';
    public const STAGE_LOST = 'perdu';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'stageHistories')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Contact $contact = null;

    #[ORM\Column(length: 32)]
    private ?string $stage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $occurredAt = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $updatedBy = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getStage(): ?string
    {
        return $this->stage;
    }

    public function setStage(string $stage): static
    {
        $this->stage = $stage;

        return $this;
    }

    public function getOccurredAt(): ?\DateTimeImmutable
    {
        return $this->occurredAt;
    }

    public function setOccurredAt(\DateTimeImmutable $occurredAt): static
    {
        $this->occurredAt = $occurredAt;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): static
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public static function getStageChoices(): array
    {
        return [
            'Prospect' => self::STAGE_PROSPECT,
            'Qualification' => self::STAGE_QUALIFICATION,
            'Proposition' => self::STAGE_PROPOSITION,
            'Négociation' => self::STAGE_NEGOTIATION,
            'Gagné' => self::STAGE_WON,
            'Perdu' => self::STAGE_LOST,
        ];
    }

    public static function getLabel(string $stage): string
    {
        $labels = array_flip(self::getStageChoices());

        return $labels[$stage] ?? $stage;
    }
}
