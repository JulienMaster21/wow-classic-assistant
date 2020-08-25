<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CharacterRepository")
 * @ORM\Table(name="`character`")
 */
class Character
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer", unique=true)
     * @Groups("attributes")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="characters")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("characterRelations")
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\PlayableClass", inversedBy="characters")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("characterRelations")
     */
    private $playableClass;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Faction", inversedBy="characters")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("characterRelations")
     */
    private $faction;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Profession", inversedBy="characters")
     * @Groups("characterRelations")
     */
    private $professions;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Recipe", inversedBy="characters")
     * @Groups("characterRelations")
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPlayableClass(): ?PlayableClass
    {
        return $this->playableClass;
    }

    public function setPlayableClass(?PlayableClass $playableClass): self
    {
        $this->playableClass = $playableClass;

        return $this;
    }

    public function getFaction(): ?Faction
    {
        return $this->faction;
    }

    public function setFaction(?Faction $faction): self
    {
        $this->faction = $faction;

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
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->contains($recipe)) {
            $this->recipes->removeElement($recipe);
        }

        return $this;
    }
}
