<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(name="categories")
 *
 * @property-read int $id
 * @property-read Category|null $parent
 * @property-read int $position
 * @property-read string $slug
 * @property-read string $name
 * @property-read string $description
 * @property-read string $descriptionSource
 * @property-read Category[] $subcategories
 * @property-read Topic[] $topics
 */
class Category {
    use SmartObject;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="subcategories")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     * @var Category|null
     */
    private $parent;

    /**
     * @ORM\Column(name="position", type="integer")
     * @var int
     */
    private $position;

    /**
     * @ORM\Column(name="slug", type="string", length=255)
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="description", type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(name="description_source", type="text")
     * @var string
     */
    private $descriptionSource;

    /**
     * @ORM\OneToMany(targetEntity="Category", mappedBy="parent")
     * @var Collection|Category[]
     */
    private $subcategories;

    /**
     * @ORM\OneToMany(targetEntity="Topic", mappedBy="category")
     * @var Collection|Topic[]
     */
    private $topics;



    public function __construct(
        ?Category $parent,
        int $position,
        string $slug,
        string $name,
        string $description,
        string $descriptionSource
    ) {
        $this->parent = $parent;
        $this->position = $position;
        $this->slug = $slug;
        $this->name = $name;
        $this->description = $description;
        $this->descriptionSource = $descriptionSource;
        $this->subcategories = new ArrayCollection();
        $this->topics = new ArrayCollection();
    }


    public function getId() : int {
        return $this->id;
    }

    public function getParent() : ?Category {
        return $this->parent;
    }

    public function getPosition() : int {
        return $this->position;
    }

    public function getSlug() : string {
        return $this->slug;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getDescription() : string {
        return $this->description;
    }

    public function getDescriptionSource() : string {
        return $this->descriptionSource;
    }

    /**
     * @return Category[]
     */
    public function getSubcategories() : array {
        return $this->subcategories->toArray();
    }

    /**
     * @return Topic[]
     */
    public function getTopics() : array {
        return $this->topics->toArray();
    }



    public function setParent(?Category $category, int $position) : void {
        $this->parent = $category;
        $this->position = $position;
    }

    public function rename(string $name) : void {
        $this->name = $name;
    }

    public function describe(string $description, string $descriptionSource) : void {
        $this->description = $description;
        $this->descriptionSource = $descriptionSource;
    }

    public function markSubcategoriesAsLoaded() : void {
        $this->subcategories->setInitialized(true);
    }

    public function addBatchLoadedSubcategory(Category $category) : void {
        $this->subcategories->hydrateAdd($category);
    }

}
