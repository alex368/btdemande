<?php

namespace App\Entity;

use App\Repository\PartnershipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnershipRepository::class)]
class Partnership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $salutation = null;

    #[ORM\Column(length: 255)]
    private ?string $linkedin = null;

    #[ORM\Column(length: 255)]
    private ?string $occupation = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $email = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $mobilePhone = null;

    #[ORM\ManyToOne(inversedBy: 'partnerships')]
    private ?FundingMechanism $fundingMechanism = null;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }
  

    public function getSalutation(): ?string
    {
        return $this->salutation;
    }

    public function setSalutation(string $salutation): static
    {
        $this->salutation = $salutation;

        return $this;
    }

    public function getLinkedin(): ?string
    {
        return $this->linkedin;
    }

    public function setLinkedin(string $linkedin): static
    {
        $this->linkedin = $linkedin;

        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(string $occupation): static
    {
        $this->occupation = $occupation;

        return $this;
    }

    public function getEmail(): ?array
    {
        return $this->email;
    }

    public function setEmail(?array $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getMobilePhone(): ?array
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?array $mobilePhone): static
    {
        $this->mobilePhone = $mobilePhone;

        return $this;
    }

    public function getFundingMechanism(): ?FundingMechanism
    {
        return $this->fundingMechanism;
    }

    public function setFundingMechanism(?FundingMechanism $fundingMechanism): static
    {
        $this->fundingMechanism = $fundingMechanism;

        return $this;
    }

}
