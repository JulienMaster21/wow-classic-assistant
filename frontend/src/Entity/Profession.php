<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProfessionRepository")
 */
class Profession
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
    private $professionLinkUrl;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $iconLinkUrl;

    /**
     * @ORM\Column(type="boolean")
     * @Groups("attributes")
     */
    private $isMainProfession;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Character", mappedBy="professions")
     * @Groups("professionRelations")
     */
    private $characters;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Recipe", mappedBy="profession", orphanRemoval=true)
     * @Groups("professionRelations")
     */
    private $recipes;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\RecipeItem", mappedBy="profession", orphanRemoval=true)
     * @Groups("professionRelations")
     */
    private $recipeItems;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Trainer", mappedBy="professions")
     * @Groups("professionRelations")
     */
    private $trainers;

    public function __construct()
    {
        $this->characters = new ArrayCollection();
        $this->recipes = new ArrayCollection();
        $this->recipeItems = new ArrayCollection();
        $this->trainers = new ArrayCollection();
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

    public function getProfessionLinkUrl(): ?string
    {
        return $this->professionLinkUrl;
    }

    public function setProfessionLinkUrl(string $professionLinkUrl): self
    {
        $this->professionLinkUrl = $professionLinkUrl;

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

    public function getIsMainProfession(): ?bool
    {
        return $this->isMainProfession;
    }

    public function setIsMainProfession(bool $isMainProfession): self
    {
        $this->isMainProfession = $isMainProfession;

        return $this;
    }

    /**
     * @return Collection|Character[]
     */
    public function getCharacters(): Collection
    {
        return $this->characters;
    }

    public function addCharacter(Character $character): self
    {
        if (!$this->characters->contains($character)) {
            $this->characters[] = $character;
            $character->addProfession($this);
        }

        return $this;
    }

    public function removeCharacter(Character $character): self
    {
        if ($this->characters->contains($character)) {
            $this->characters->removeElement($character);
            $character->removeProfession($this);
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
            $recipe->setProfession($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): self
    {
        if ($this->recipes->contains($recipe)) {
            $this->recipes->removeElement($recipe);
            // set the owning side to null (unless already changed)
            if ($recipe->getProfession() === $this) {
                $recipe->setProfession(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|RecipeItem[]
     */
    public function getRecipeItems(): Collection
    {
        return $this->recipeItems;
    }

    public function addRecipeItem(RecipeItem $recipeItem): self
    {
        if (!$this->recipeItems->contains($recipeItem)) {
            $this->recipeItems[] = $recipeItem;
            $recipeItem->setProfession($this);
        }

        return $this;
    }

    public function removeRecipeItem(RecipeItem $recipeItem): self
    {
        if ($this->recipeItems->contains($recipeItem)) {
            $this->recipeItems->removeElement($recipeItem);
            // set the owning side to null (unless already changed)
            if ($recipeItem->getProfession() === $this) {
                $recipeItem->setProfession(null);
            }
        }

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
            $trainer->addProfession($this);
        }

        return $this;
    }

    public function removeTrainer(Trainer $trainer): self
    {
        if ($this->trainers->contains($trainer)) {
            $this->trainers->removeElement($trainer);
            $trainer->removeProfession($this);
        }

        return $this;
    }
}
