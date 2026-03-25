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
}
