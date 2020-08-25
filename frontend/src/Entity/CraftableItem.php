<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(
 *     repositoryClass = "App\Repository\CraftableItemRepository"
 * )
 * @UniqueEntity(
 *     fields   = {"name"},
 *     message  = "The name is already used."
 * )
 * @UniqueEntity(
 *     fields   = {"itemLinkUrl"},
 *     message  = "The item link is already used."
 * )
 */
class CraftableItem
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(
     *     type     = "integer",
     *     unique   = true
     * )
     * @Groups("attributes")
     */
    private $id;

    /**
     * @ORM\Column(
     *     type     = "string",
     *     length   = 255,
     *     unique   = true
     * )
     * @Groups("attributes")
     * @Assert\Length(
     *     min              = 1,
     *     max              = 255,
     *     minMessage       = "The name needs to be at least {{ limit }} characters long.",
     *     maxMessage       = "The name can't be longer than {{ limit }} characters.",
     *     allowEmptyString = false
     * )
     * @Assert\Type(
     *     type     = "string",
     *     message  = "The name needs to be of the type 'string'."
     * )
     */
    private $name;

    /**
     * @ORM\Column(
     *     type     = "string",
     *     length   = 255,
     *     unique   = true
     * )
     * @Groups("attributes")
     * @Assert\Length(
     *     min              = 1,
     *     max              = 255,
     *     minMessage       = "The item link url needs to be at least {{ limit }} characters long.",
     *     maxMessage       = "The item link url can't be longer than {{ limit }} characters.",
     *     allowEmptyString = false
     * )
     * @Assert\Type(
     *     type     = "string",
     *     message  = "The item link url needs to be of the type 'string'."
     * )
     * @Assert\Url(message = "The item link url needs to be a valid url.")
     */
    private $itemLinkUrl;

    /**
     * @ORM\Column(
     *     type     = "string",
     *     length   = 255
     * )
     * @Groups("attributes")
     * @Assert\Length(
     *     min              = 1,
     *     max              = 255,
     *     minMessage       = "The icon link url needs to be at least {{ limit }} characters long.",
     *     maxMessage       = "The icon link url can't be longer than {{ limit }} characters.",
     *     allowEmptyString = false
     * )
     * @Assert\Type(
     *     type     = "string",
     *     message  = "The icon link url needs to be of the type 'string'."
     * )
     * @Assert\Url(message = "The icon link url needs to be a valid url.")
     */
    private $iconLinkUrl;

    /**
     * @ORM\Column(
     *     type = "string",
     *     length = 255
     * )
     * @Groups("attributes")
     * @Assert\Choice(
     *     choices = {
     *          "Not equipable",
     *          "Head",
     *          "Neck",
     *          "Shoulder",
     *          "Chest",
     *          "Shirt",
     *          "Back",
     *          "Wrist",
     *          "Hands",
     *          "Waist",
     *          "Legs",
     *          "Feet",
     *          "Trinket",
     *          "Two-Hand",
     *          "One-Hand",
     *          "Main Hand",
     *          "Shield",
     *          "Ranged",
     *          "Projectile",
     *          "Bag"
     *     },
     *     message = "The selected item slot isn't a known item slot."
     * )
     * @Assert\Type(
     *     type     = "string",
     *     message  = "The item slot needs to be of the type 'string'."
     * )
     */
    private $itemSlot;

    /**
     * @ORM\Column(
     *     type     = "integer",
     *     nullable = true)
     * @Groups("attributes")
     * @Assert\Type(
     *     type     = "integer",
     *     message  = "The sell price needs to be a number."
     * )
     */
    private $sellPrice;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Recipe", mappedBy="craftableItem")
     * @Groups("craftableItemRelations")
     * @MaxDepth(1)
     */
    private $recipes;

    public function __construct() {

        $this->recipes = new ArrayCollection();
    }

    public function getId(): ?int {

        return $this->id;
    }

    public function getName(): ?string {

        return $this->name;
    }

    public function setName(string $name): self {

        $this->name = $name;

        return $this;
    }

    public function getItemLinkUrl(): ?string {

        return $this->itemLinkUrl;
    }

    public function setItemLinkUrl(string $itemLinkUrl): self {

        $this->itemLinkUrl = $itemLinkUrl;

        return $this;
    }

    public function getIconLinkUrl(): ?string {

        return $this->iconLinkUrl;
    }

    public function setIconLinkUrl(string $iconLinkUrl): self {

        $this->iconLinkUrl = $iconLinkUrl;

        return $this;
    }

    public function getItemSlot(): ?string {

        return $this->itemSlot;
    }

    public function setItemSlot(string $itemSlot): self {

        $this->itemSlot = $itemSlot;

        return $this;
    }

    public function getSellPrice(): ?int {

        return $this->sellPrice;
    }

    public function setSellPrice(?int $sellPrice): self {

        $this->sellPrice = $sellPrice;

        return $this;
    }

    /**
     * @return Collection|Recipe[]
     */
    public function getRecipes(): Collection {
        return $this->recipes;
    }

    public function addRecipe(Recipe $recipe): self {

        if (!$this->recipes->contains($recipe)) {
            $this->recipes[] = $recipe;
            $recipe->setCraftableItem($this);
        }

        return $this;
    }

    public function removeRecipe(Recipe $recipe): self {

        if ($this->recipes->contains($recipe)) {
            $this->recipes->removeElement($recipe);
            // set the owning side to null (unless already changed)
            if ($recipe->getCraftableItem() === $this) {
                $recipe->setCraftableItem(null);
            }
        }

        return $this;
    }
}
