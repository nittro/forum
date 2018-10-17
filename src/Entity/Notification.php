<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(
 *     name="notifications",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_post_notification", columns={"user_id", "post_id"})
 *     }
 * )
 *
 * @property-read int $id
 * @property-read User $user
 * @property-read Post $post
 * @property-read \DateTimeImmutable $createdOn
 * @property-read int $level
 * @property-read bool $displayed
 */
class Notification {
    use SmartObject;

    public const INSTANT = 1,
        WEEKLY = 2,
        DAILY = 3;


    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Post")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Post
     */
    private $post;

    /**
     * @ORM\Column(name="created_on", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $createdOn;

    /**
     * @ORM\Column(name="level", type="integer")
     * @var int
     */
    private $level;

    /**
     * @ORM\Column(name="displayed", type="boolean")
     * @var bool
     */
    private $displayed;


    public function __construct(User $user, Post $post, int $level) {
        $this->user = $user;
        $this->post = $post;
        $this->createdOn = new \DateTimeImmutable();
        $this->level = $level;
        $this->displayed = false;
    }


    public function getId() : int {
        return $this->id;
    }

    public function getUser() : User {
        return $this->user;
    }

    public function getPost() : Post {
        return $this->post;
    }

    public function getCreatedOn() : \DateTimeImmutable {
        return $this->createdOn;
    }

    public function getLevel() : int {
        return $this->level;
    }

    public function isDisplayed() : bool {
        return $this->displayed;
    }


    public function setDisplayed() : void {
        $this->displayed = true;
    }

}
