<?php

namespace App\Entity;

use App\Repository\OpportunityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpportunityRepository::class)]
class Opportunity
{
    public const LEAD_SOURCE_LINKEDIN = 'linkedin';
    public const LEAD_SOURCE_BUSINESS_NETWORK = 'reseau_affaires';
    public const LEAD_SOURCE_EVENTS = 'evenements';
    public const LEAD_SOURCE_EMAILS = 'mails';
    public const LEAD_SOURCE_CALLS = 'tels';
    public const LEAD_SOURCE_BTD_SITE = 'site_btd';
    public const LEAD_SOURCE_BTD_TOOL = 'outil_btd';
    public const LEAD_SOURCE_REFERRAL_PARTNER = 'recommandation_partenaire';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $leadSource = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stage = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'opportunities')]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'opportunity')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $commercialReferent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $leadSourceDetail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLeadSource(): ?string
    {
        return $this->leadSource;
    }

    public function setLeadSource(string $leadSource): static
    {
        $this->leadSource = $leadSource;

        return $this;
    }

    public function getStage(): ?string
    {
        return $this->stage;
    }

    public function setStage(?string $stage): static
    {
        $this->stage = $stage;

        return $this;
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getCommercialReferent(): ?User
    {
        return $this->commercialReferent;
    }

    public function setCommercialReferent(?User $commercialReferent): static
    {
        $this->commercialReferent = $commercialReferent;

        return $this;
    }

    public function getLeadSourceDetail(): ?string
    {
        return $this->leadSourceDetail;
    }

    public function setLeadSourceDetail(?string $leadSourceDetail): static
    {
        $this->leadSourceDetail = $leadSourceDetail;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public static function getLeadSourceChoices(): array
    {
        return [
            'LinkedIn' => self::LEAD_SOURCE_LINKEDIN,
            'Réseau d’affaires' => self::LEAD_SOURCE_BUSINESS_NETWORK,
            'Événements' => self::LEAD_SOURCE_EVENTS,
            'Mails' => self::LEAD_SOURCE_EMAILS,
            'Tels' => self::LEAD_SOURCE_CALLS,
            'Site BTD' => self::LEAD_SOURCE_BTD_SITE,
            'Outil BTD' => self::LEAD_SOURCE_BTD_TOOL,
            'Recommandation / Partenaire' => self::LEAD_SOURCE_REFERRAL_PARTNER,
        ];
    }

    public function getLeadSourceLabel(): string
    {
        $labels = array_flip(self::getLeadSourceChoices());

        return $labels[$this->leadSource ?? ''] ?? (string) $this->leadSource;
    }

    public function isReferralPartnerSource(): bool
    {
        return $this->leadSource === self::LEAD_SOURCE_REFERRAL_PARTNER;
    }
}
