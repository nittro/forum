<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(name="posts")
 *
 * @property-read int $id
 * @property-read Topic $topic
 * @property-read User $author
 * @property-read \DateTimeImmutable $postedOn
 * @property-read string $text
 * @property-read string $textSource
 */
class Post {
    use SmartObject;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Topic", inversedBy="posts")
     * @ORM\JoinColumn(name="topic_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Topic
     */
    private $topic;

    /**
     * @ORM\ManyToOne(targetEntity="User", fetch="EAGER")
     * @ORM\JoinColumn(name="author_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var User
     */
    private $author;

    /**
     * @ORM\Column(name="posted_on", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $postedOn;

    /**
     * @ORM\Column(name="text", type="text")
     * @var string
     */
    private $text;

    /**
     * @ORM\Column(name="text_source", type="text")
     * @var string
     */
    private $textSource;


    public function __construct(Topic $topic, User $author, string $text, string $textSource) {
        $this->topic = $topic;
        $this->author = $author;
        $this->postedOn = new \DateTimeImmutable();
        $this->text = $text;
        $this->textSource = $textSource;
    }

    public function getId() : int {
        return $this->id;
    }

    public function getTopic() : Topic {
        return $this->topic;
    }

    public function getAuthor() : User {
        return $this->author;
    }

    public function getPostedOn() : \DateTimeImmutable {
        return $this->postedOn;
    }

    public function getText() : string {
        return $this->text;
    }

    public function getTextSource() : string {
        return $this->textSource;
    }


    public function updateText(string $text, string $source) : void {
        $this->text = $text;
        $this->textSource = $source;
    }

}
