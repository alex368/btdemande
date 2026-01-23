<?php

namespace App\Entity;

use App\Repository\FunderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FunderRepository::class)]
class Funder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $campanyName = null;

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


