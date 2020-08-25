<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VendorRepository")
 */
class Vendor
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
    private $vendorLinkUrl;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $reactionToAlliance;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $reactionToHorde;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Reagent", mappedBy="vendors")
     * @Groups("vendorRelations")
     */
    private $reagents;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Location", inversedBy="vendors")
     * @Groups("vendorRelations")
     */
    private $locations;

    public function __construct() {

        $this->reagents = new ArrayCollection();
        $this->locations = new ArrayCollection();
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

    public function getVendorLinkUrl(): ?string
    {
        return $this->vendorLinkUrl;
    }

    public function setVendorLinkUrl(string $vendorLinkUrl): self
    {
        $this->vendorLinkUrl = $vendorLinkUrl;

        return $this;
    }

    public function getReactionToAlliance(): ?string
    {
        return $this->reactionToAlliance;
    }

    public function setReactionToAlliance(string $reactionToAlliance): self
    {
        $this->reactionToAlliance = $reactionToAlliance;

        return $this;
    }

    public function getReactionToHorde(): ?string
    {
        return $this->reactionToHorde;
    }

    public function setReactionToHorde(string $reactionToHorde): self
    {
        $this->reactionToHorde = $reactionToHorde;

        return $this;
    }

    /**
     * @return Collection|Reagent[]
     */
    public function getReagents(): Collection
    {
        return $this->reagents;
    }

    public function addReagent(Reagent $reagent): self
    {
        if (!$this->reagents->contains($reagent)) {
            $this->reagents[] = $reagent;
            $reagent->addVendor($this);
        }

        return $this;
    }

    public function removeReagent(Reagent $reagent): self
    {
        if ($this->reagents->contains($reagent)) {
            $this->reagents->removeElement($reagent);
            $reagent->removeVendor($this);
        }

        return $this;
    }

    /**
     * @return Collection|Location[]
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations[] = $location;
        }

        return $this;
    }

    public function removeLocation(Location $location): self
    {
        if ($this->locations->contains($location)) {
            $this->locations->removeElement($location);
        }

        return $this;
    }
}
