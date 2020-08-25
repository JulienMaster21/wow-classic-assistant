<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LocationRepository")
 */
class Location
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", unique=true)
     * @Groups("attributes")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups("attributes")
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups("attributes")
     */
    private $locationLinkUrl;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $factionStatus;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Trainer", mappedBy="location")
     * @Groups("locationRelations")
     */
    private $trainers;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Vendor", mappedBy="locations")
     * @Groups("locationRelations")
     */
    private $vendors;

    public function __construct()
    {
        $this->trainers = new ArrayCollection();
        $this->vendors = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getLocationLinkUrl(): ?string
    {
        return $this->locationLinkUrl;
    }

    public function setLocationLinkUrl(string $locationLinkUrl): self
    {
        $this->locationLinkUrl = $locationLinkUrl;

        return $this;
    }

    public function getFactionStatus(): ?string
    {
        return $this->factionStatus;
    }

    public function setFactionStatus(string $factionStatus): self
    {
        $this->factionStatus = $factionStatus;

        return $this;
    }

    /**
     * @return Collection|Trainer[]
     */
    public function getTrainers(): Collection
    {
        return $this->trainers;
    }

    public function addTrainer(Trainer $trainer): self
    {
        if (!$this->trainers->contains($trainer)) {
            $this->trainers[] = $trainer;
            $trainer->setLocation($this);
        }

        return $this;
    }

    public function removeTrainer(Trainer $trainer): self
    {
        if ($this->trainers->contains($trainer)) {
            $this->trainers->removeElement($trainer);
            // set the owning side to null (unless already changed)
            if ($trainer->getLocation() === $this) {
                $trainer->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Vendor[]
     */
    public function getVendors(): Collection
    {
        return $this->vendors;
    }

    public function addVendor(Vendor $vendor): self
    {
        if (!$this->vendors->contains($vendor)) {
            $this->vendors[] = $vendor;
            $vendor->addLocation($this);
        }

        return $this;
    }

    public function removeVendor(Vendor $vendor): self
    {
        if ($this->vendors->contains($vendor)) {
            $this->vendors->removeElement($vendor);
            $vendor->removeLocation($this);
        }

        return $this;
    }
}
