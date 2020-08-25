<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\TrainerRepository")
 */
class Trainer
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
    private $trainerLinkUrl;

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
     * @ORM\ManyToMany(targetEntity="App\Entity\Profession", inversedBy="trainers")
     * @Groups("trainerRelations")
     */
    private $professions;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Location", inversedBy="trainers")
     * @Groups("trainerRelations")
     */
    private $location;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Recipe", mappedBy="trainers")
     * @Groups("trainerRelations")
     */
    private $recipes;

    public function __construct()
    {
        $this->professions = new ArrayCollection();
        $this->recipes = new ArrayCollection();
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

    public function getTrainerLinkUrl(): ?string
    {
        return $this->trainerLinkUrl;
    }

    public function setTrainerLinkUrl(string $trainerLinkUrl): self
    {
        $this->trainerLinkUrl = $trainerLinkUrl;

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
     * @return Collection|Profession[]
     */
    public function getProfessions(): Collection
    {
        return $this->professions;
    }

    public function addProfession(Profession $profession): self
    {
        if (!$this->professions->contains($profession)) {
            $this->professions[] = $profession;
        }

        return $this;
    }

    public function removeProfession(Profession $profession): self
    {
        if ($this->professions->contains($profession)) {
            $this->professions->removeElement($profession);
        }

        return $this;
    }

    public function getLocation(): ?Location
    {
        return $this->location;
    }

    public function setLocation(?Location $location): self
    {
        $this->location = $location;

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
            $recipe->addTrainer($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->contains($recipe)) {
            $this->recipes->removeElement($recipe);
            $recipe->removeTrainer($this);
        }

        return $this;
    }
}
