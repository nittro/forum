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
 * @ORM\Table(name="topics")
 *
 * @property-read int $id
 * @property-read Category $category
 * @property-read User $author
 * @property-read Post $firstPost
 * @property-read Post $lastPost
 * @property-read string $slug
 * @property-read \DateTimeImmutable $createdOn
 * @property-read string $title
 * @property-read Post[] $posts
 */
class Topic {
    use SmartObject;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="topics")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Category
     */
    private $category;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $author;

    /**
     * @ORM\OneToOne(targetEntity="Post")
     * @ORM\JoinColumn(name="first_post_id", referencedColumnName="id", onDelete="SET NULL")
     * @var Post
     */
    private $firstPost;

    /**
     * @ORM\OneToOne(targetEntity="Post")
     * @ORM\JoinColumn(name="last_post_id", referencedColumnName="id", onDelete="SET NULL")
     * @var Post
     */
    private $lastPost;

    /**
     * @ORM\Column(name="slug", type="string", length=255)
     * @var string
     */
    private $slug;

    /**
     * @ORM\Column(name="created_on", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $createdOn;

    /**
     * @ORM\Column(name="title", type="string", length=255)
     * @var string
     */
    private $title;

    /**
     * @ORM\OneToMany(targetEntity="Post", mappedBy="topic")
     * @var Collection|Post[]
     */
    private $posts;



    public function __construct(Category $category, User $author, string $slug, string $title) {
        $this->category = $category;
        $this->author = $author;
        $this->slug = $slug;
        $this->createdOn = new \DateTimeImmutable();
        $this->title = $title;
        $this->posts = new ArrayCollection();
    }

    public function getId() : int {
        return $this->id;
    }

    public function getCategory() : Category {
        return $this->category;
    }

    public function getAuthor() : User {
        return $this->author;
    }

    public function getFirstPost() : Post {
        return $this->firstPost;
    }

    public function getLastPost() : Post {
        return $this->lastPost;
    }

    public function getSlug() : string {
        return $this->slug;
    }

    public function getCreatedOn() : \DateTimeImmutable {
        return $this->createdOn;
    }

    public function getTitle() : string {
        return $this->title;
    }

    /**
     * @return Post[]
     */
    public function getPosts() : array {
        return $this->posts->toArray();
    }


    public function addPost(Post $post) : void {
        $this->posts->add($post);
        $this->lastPost = $post;

        if (!$this->firstPost) {
            $this->firstPost = $post;
        }
    }

    public function setLastPost(Post $post) : void {
        $this->lastPost = $post;
    }

    public function setTitle(string $title) : void {
        $this->title = $title;
    }

    public function setSlug(string $slug) : void {
        $this->slug = $slug;
    }

}
