<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\FunderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
], normalizationContext: ['groups' => ['funder:read']], denormalizationContext: ['groups' => ['funder:write']])]
#[ORM\Entity(repositoryClass: FunderRepository::class)]
class Funder
{
    #[Groups(['funder:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['funder:read', 'funder:write'])]
    #[ORM\Column(length: 255)]
    private ?string $campanyName = null;

    #[Groups(['funder:read', 'funder:write'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;




    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampanyName(): ?string
    {
        return $this->campanyName;
    }

    public function setCampanyName(string $campanyName): static
    {
        $this->campanyName = $campanyName;

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

}

