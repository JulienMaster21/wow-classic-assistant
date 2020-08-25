<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SourceRepository")
 */
class Source
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
     * @ORM\ManyToMany(targetEntity="App\Entity\Reagent", mappedBy="sources")
     * @Groups("sourceRelations")
     */
    private $reagents;

    public function __construct()
    {
        $this->reagents = new ArrayCollection();
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
            $reagent->addSource($this);
        }

        return $this;
    }

    public function removeReagent(Reagent $reagent): self
    {
        if ($this->reagents->contains($reagent)) {
            $this->reagents->removeElement($reagent);
            $reagent->removeSource($this);
        }

        return $this;
    }
}
