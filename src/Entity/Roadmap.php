<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\RoadmapRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
], normalizationContext: ['groups' => ['roadmap:read']], denormalizationContext: ['groups' => ['roadmap:write']])]
#[ORM\Entity(repositoryClass: RoadmapRepository::class)]
class Roadmap
{
    public const EXPENSE_TYPE_TREASURY = 'trésorerie';
    public const EXPENSE_TYPE_R_AND_D = 'R&D';
    public const EXPENSE_TYPE_COMMERCIAL = 'commercial';
    public const EXPENSE_TYPE_HR = 'RH';
    public const EXPENSE_TYPE_TANGIBLE_INVESTMENT = 'investissement corporel';
    public const EXPENSE_TYPE_INTANGIBLE_INVESTMENT = 'investissement incorporel';

    #[Groups(['roadmap:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['roadmap:read', 'roadmap:write'])]
    #[ORM\ManyToOne(inversedBy: 'roadmaps')]
    private ?Product $product = null;

    #[Groups(['roadmap:read', 'roadmap:write'])]
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $date = null;

    #[Groups(['roadmap:read', 'roadmap:write'])]
    #[ORM\ManyToOne(inversedBy: 'roadmaps')]
    private ?Campany $campany = null;

    #[Groups(['roadmap:read', 'roadmap:write'])]
    #[ORM\Column(nullable: true)]
    private ?int $estimatedAmount = null;

    #[Groups(['roadmap:read', 'roadmap:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $expenseType = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?FundingRequest $fundingRequest = null;

    #[ORM\Column(nullable: true)]
    private ?int $position = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
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

    public function getEstimatedAmount(): ?int
    {
        return $this->estimatedAmount;
    }

    public function setEstimatedAmount(?int $estimatedAmount): static
    {
        $this->estimatedAmount = $estimatedAmount;

        return $this;
    }

    public function getExpenseType(): ?string
    {
        return $this->expenseType;
    }

    public function setExpenseType(?string $expenseType): static
    {
        $this->expenseType = $expenseType;

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

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public static function getExpenseTypeChoices(): array
    {
        return [
            'Trésorerie' => self::EXPENSE_TYPE_TREASURY,
            'R&D' => self::EXPENSE_TYPE_R_AND_D,
            'Commercial' => self::EXPENSE_TYPE_COMMERCIAL,
            'RH' => self::EXPENSE_TYPE_HR,
            'Investissement corporel' => self::EXPENSE_TYPE_TANGIBLE_INVESTMENT,
            'Investissement incorporel' => self::EXPENSE_TYPE_INTANGIBLE_INVESTMENT,
        ];
    }
}
