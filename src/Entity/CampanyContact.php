<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CampanyContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(operations: [
    new Get(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new GetCollection(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Post(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
    new Patch(security: "is_granted('ROLE_ADMIN') or is_granted('ROLE_COLLABORATOR') or is_granted('ROLE_COLLABORATEUR')"),
], normalizationContext: ['groups' => ['campany_contact:read']], denormalizationContext: ['groups' => ['campany_contact:write']])]
#[ORM\Entity(repositoryClass: CampanyContactRepository::class)]
class CampanyContact
{
    #[Groups(['campany_contact:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legalName = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $sector = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adress = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siren = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $creationDate = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stage = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logo = null;

    #[Groups(['campany_contact:read', 'campany_contact:write'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $projectName = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\ManyToMany(targetEntity: Contact::class, inversedBy: 'campanyContacts')]
    private Collection $contact;

    public function __construct()
    {
        $this->contact = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLegalName(): ?string
    {
        return $this->legalName;
    }

    public function setLegalName(?string $legalName): static
    {
        $this->legalName = $legalName;

        return $this;
    }

    public function getSector(): ?string
    {
        return $this->sector;
    }

    public function setSector(?string $sector): static
    {
        $this->sector = $sector;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(?string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getSiren(): ?string
    {
        return $this->siren;
    }

    public function setSiren(?string $siren): static
    {
        $this->siren = $siren;

        return $this;
    }

    public function getCreationDate(): ?\DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(?\DateTime $creationDate): static
    {
        $this->creationDate = $creationDate;

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

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(?string $logo): static
    {
        $this->logo = $logo;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(?string $projectName): static
    {
        $this->projectName = $projectName;

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContact(): Collection
    {
        return $this->contact;
    }

    public function addContact(Contact $contact): static
    {
        if (!$this->contact->contains($contact)) {
            $this->contact->add($contact);
        }

        return $this;
    }

    public function removeContact(Contact $contact): static
    {
        $this->contact->removeElement($contact);

        return $this;
    }
}
