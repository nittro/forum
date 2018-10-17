<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(
 *     name="topic_subscriptions",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_topic_subscription", columns={"user_id", "topic_id"})
 *     }
 * )
 *
 * @property-read int $id
 * @property-read User $user
 * @property-read Topic $topic
 * @property-read int $notificationLevel
 * @property-read int|null $lastRead
 */
class TopicSubscription {
    use SmartObject, SubscriptionTrait;

    public const NOTIFICATIONS_OFF = 0,
        NOTIFICATIONS_INSTANT = Notification::INSTANT;


    /**
     * @ORM\ManyToOne(targetEntity="Topic")
     * @ORM\JoinColumn(name="topic_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Topic
     */
    private $topic;

    /**
     * @ORM\Column(name="last_read", type="integer", nullable=true)
     * @var int
     */
    private $lastRead;


    public function __construct(User $user, Topic $topic, int $notificationLevel = self::NOTIFICATIONS_OFF) {
        $this->user = $user;
        $this->topic = $topic;
        $this->notificationLevel = $notificationLevel;
    }

    public function getTopic() : Topic {
        return $this->topic;
    }

    public function getLastRead() : ?int {
        return $this->lastRead;
    }

    public function markAsRead(Post $post) : void {
        $this->lastRead = $post->getId();
    }

}
