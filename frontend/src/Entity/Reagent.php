<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ReagentRepository")
 */
class Reagent {
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
    private $itemLinkUrl;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $iconLinkUrl;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Recipe", mappedBy="reagents")
     * @Groups("reagentRelations")
     */
    private $recipes;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Source", inversedBy="reagents")
     * @Groups("reagentRelations")
     */
    private $sources;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Vendor", inversedBy="reagents")
     * @Groups("reagentRelations")
     */
    private $vendors;

    public function __construct() {

        $this->recipes = new ArrayCollection();
        $this->sources = new ArrayCollection();
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

    public function getItemLinkUrl(): ?string
    {
        return $this->itemLinkUrl;
    }

    public function setItemLinkUrl(string $itemLinkUrl): self
    {
        $this->itemLinkUrl = $itemLinkUrl;

        return $this;
    }

    public function getIconLinkUrl(): ?string
    {
        return $this->iconLinkUrl;
    }

    public function setIconLinkUrl(string $iconLinkUrl): self
    {
        $this->iconLinkUrl = $iconLinkUrl;

        return $this;
    }

    /**
     * @return Collection|Recipe[]
     */
    public function getRecipes(): Collection
    {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe): self
    {
        if (!$this->recipes->contains($recipe)) {
            $this->recipes[] = $recipe;
            $recipe->addReagent($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->contains($recipe)) {
            $this->recipes->removeElement($recipe);
            $recipe->removeReagent($this);
        }

        return $this;
    }

    /**
     * @return Collection|Source[]
     */
    public function getSources(): Collection
    {
        return $this->sources;
    }

    public function addSource(Source $source): self
    {
        if (!$this->sources->contains($source)) {
            $this->sources[] = $source;
        }

        return $this;
    }

    public function removeSource(Source $source): self
    {
        if ($this->sources->contains($source)) {
            $this->sources->removeElement($source);
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
        }

        return $this;
    }

    public function removeVendor(Vendor $vendor): self
    {
        if ($this->vendors->contains($vendor)) {
            $this->vendors->removeElement($vendor);
        }

        return $this;
    }
}
