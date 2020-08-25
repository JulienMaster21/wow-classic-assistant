<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * @ORM\Entity(
 *     repositoryClass = "App\Repository\RecipeRepository"
 * )
 */
class Recipe
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(
     *     type     = "integer",
     *     unique   = true)
     * @Groups("attributes")
     */
    private $id;

    /**
     * @ORM\Column(
     *     type     = "string",
     *     length   = 255)
     * @Groups("attributes")
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $difficultyRequirement;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $difficultyCategory_1;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $difficultyCategory_2;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $difficultyCategory_3;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $difficultyCategory_4;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Groups("attributes")
     */
    private $recipeLinkUrl;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups("attributes")
     */
    private $iconLinkUrl;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $minimumAmountCreated;

    /**
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $maximumAmountCreated;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups("attributes")
     */
    private $trainingCost;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\RecipeItem")
     * @Groups("recipeRelations")
     * @MaxDepth(1)
     */
    private $recipeItem;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\CraftableItem", inversedBy="recipes")
     * @Groups("recipeRelations")
     * @MaxDepth(1)
     */
    private $craftableItem;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Profession", inversedBy="recipes")
     * @ORM\JoinColumn(nullable=false)
     * @Groups("recipeRelations")
     * @MaxDepth(1)
     */
    private $profession;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Trainer", inversedBy="recipes")
     * @Groups("recipeRelations")
     * @MaxDepth(1)
     */
    private $trainers;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Reagent", inversedBy="recipes")
     * @Groups("recipeRelations")
     * @MaxDepth(1)
     */
    private $reagents;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Character", mappedBy="recipes")
     * @Groups("recipeRelations")
     * @MaxDepth(1)
     */
    private $characters;

    public function __construct()
    {
        $this->trainers = new ArrayCollection();
        $this->reagents = new ArrayCollection();
        $this->characters = new ArrayCollection();
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

    public function getDifficultyRequirement(): ?int
    {
        return $this->difficultyRequirement;
    }

    public function setDifficultyRequirement(int $difficultyRequirement): self
    {
        $this->difficultyRequirement = $difficultyRequirement;

        return $this;
    }

    public function getDifficultyCategory1(): ?int
    {
        return $this->difficultyCategory_1;
    }

    public function setDifficultyCategory1(int $difficultyCategory_1): self
    {
        $this->difficultyCategory_1 = $difficultyCategory_1;

        return $this;
    }

    public function getDifficultyCategory2(): ?int
    {
        return $this->difficultyCategory_2;
    }

    public function setDifficultyCategory2(int $difficultyCategory_2): self
    {
        $this->difficultyCategory_2 = $difficultyCategory_2;

        return $this;
    }

    public function getDifficultyCategory3(): ?int
    {
        return $this->difficultyCategory_3;
    }

    public function setDifficultyCategory3(int $difficultyCategory_3): self
    {
        $this->difficultyCategory_3 = $difficultyCategory_3;

        return $this;
    }

    public function getDifficultyCategory4(): ?int
    {
        return $this->difficultyCategory_4;
    }

    public function setDifficultyCategory4(int $difficultyCategory_4): self
    {
        $this->difficultyCategory_4 = $difficultyCategory_4;

        return $this;
    }

    public function getRecipeLinkUrl(): ?string
    {
        return $this->recipeLinkUrl;
    }

    public function setRecipeLinkUrl(string $recipeLinkUrl): self
    {
        $this->recipeLinkUrl = $recipeLinkUrl;

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

    public function getMinimumAmountCreated(): ?int
    {
        return $this->minimumAmountCreated;
    }

    public function setMinimumAmountCreated(int $minimumAmountCreated): self
    {
        $this->minimumAmountCreated = $minimumAmountCreated;

        return $this;
    }

    public function getMaximumAmountCreated(): ?int
    {
        return $this->maximumAmountCreated;
    }

    public function setMaximumAmountCreated(int $maximumAmountCreated): self
    {
        $this->maximumAmountCreated = $maximumAmountCreated;

        return $this;
    }

    public function getTrainingCost(): ?int
    {
        return $this->trainingCost;
    }

    public function setTrainingCost(?int $trainingCost): self
    {
        $this->trainingCost = $trainingCost;

        return $this;
    }

    public function getRecipeItem(): ?RecipeItem
    {
        return $this->recipeItem;
    }

    public function setRecipeItem(?RecipeItem $recipeItem): self
    {
        $this->recipeItem = $recipeItem;

        return $this;
    }

    public function getCraftableItem(): ?CraftableItem
    {
        return $this->craftableItem;
    }

    public function setCraftableItem(?CraftableItem $craftableItem): self
    {
        $this->craftableItem = $craftableItem;

        return $this;
    }

    public function getProfession(): ?Profession
    {
        return $this->profession;
    }

    public function setProfession(?Profession $profession): self
    {
        $this->profession = $profession;

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
        }

        return $this;
    }

    public function removeTrainer(Trainer $trainer): self
    {
        if ($this->trainers->contains($trainer)) {
            $this->trainers->removeElement($trainer);
        }

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
        }

        return $this;
    }

    public function removeReagent(Reagent $reagent): self
    {
        if ($this->reagents->contains($reagent)) {
            $this->reagents->removeElement($reagent);
        }

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
            $character->addRecipe($this);
        }

        return $this;
    }

    public function removeCharacter(Character $character): self
    {
        if ($this->characters->contains($character)) {
            $this->characters->removeElement($character);
            $character->removeRecipe($this);
        }

        return $this;
    }
}
