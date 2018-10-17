<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(name="mails")
 *
 * @property-read int $id
 * @property-read User $recipient
 * @property-read Topic|null $topic
 * @property-read Post|null $post
 * @property-read string|null $tokenHash
 * @property-read \DateTimeImmutable $sentOn
 * @property-read string $to
 */
class Mail {
    use SmartObject;


    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="recipient_id", referencedColumnName="id", onDelete="CASCADE")
     * @var User
     */
    private $recipient;

    /**
     * @ORM\ManyToOne(targetEntity="Topic")
     * @ORM\JoinColumn(name="topic_id", referencedColumnName="id", onDelete="SET NULL")
     * @var Topic
     */
    private $topic;

    /**
     * @ORM\ManyToOne(targetEntity="Post")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id", onDelete="SET NULL")
     * @var Post
     */
    private $post;

    /**
     * @ORM\Column(name="token", type="string", length=72, nullable=true)
     * @var string
     */
    private $tokenHash;

    /**
     * @ORM\Column(name="sent_on", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $sentOn;

    /**
     * @ORM\Column(name="to_address", type="string", length=255)
     * @var string
     */
    private $to;


    public function __construct(
        User $recipient,
        ?Topic $topic,
        ?Post $post,
        ?string $token,
        string $to
    ) {
        $this->recipient = $recipient;
        $this->topic = $topic;
        $this->post = $post;
        $this->tokenHash = $token ? password_hash($token, PASSWORD_BCRYPT, ['cost' => 12]) : null;
        $this->sentOn = new \DateTimeImmutable();
        $this->to = $to;
    }

    public function getId() : int {
        return $this->id;
    }

    public function getRecipient() : User {
        return $this->recipient;
    }

    public function getTopic() : ?Topic {
        return $this->topic;
    }

    public function getPost() : ?Post {
        return $this->post;
    }

    public function isValid(string $token) : bool {
        return $this->tokenHash && password_verify($token, $this->tokenHash);
    }

    public function getSentOn() : \DateTimeImmutable {
        return $this->sentOn;
    }

    public function getTo() : string {
        return $this->to;
    }


    public function resetToken() : void {
        $this->tokenHash = null;
    }

}
