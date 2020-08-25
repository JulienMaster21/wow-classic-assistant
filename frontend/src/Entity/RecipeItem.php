<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\RecipeItemRepository")
 */
class RecipeItem
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
     * @ORM\Column(type="integer")
     * @Groups("attributes")
     */
    private $requiredSkillLevel;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Profession", inversedBy="recipeItems")
     * @Groups("recipeItemRelations")
     */
    private $profession;

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

    public function getRequiredSkillLevel(): ?int
    {
        return $this->requiredSkillLevel;
    }

    public function setRequiredSkillLevel(int $requiredSkillLevel): self
    {
        $this->requiredSkillLevel = $requiredSkillLevel;

        return $this;
    }

    public function getProfession(): ?Profession
    {
        return $this->profession;
    }

    public function setProfession(Profession $profession): self
    {
        $this->profession = $profession;

        return $this;
    }
}
