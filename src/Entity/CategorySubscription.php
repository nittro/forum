<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(
 *     name="category_subscriptions",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="unique_category_subscription", columns={"user_id", "category_id"})
 *     }
 * )
 *
 * @property-read int $id
 * @property-read User $user
 * @property-read Category $category
 * @property-read int $notificationLevel
 */
class CategorySubscription {
    use SmartObject, SubscriptionTrait;

    public const NOTIFICATIONS_OFF = 0,
        NOTIFICATIONS_WEEKLY = Notification::WEEKLY,
        NOTIFICATIONS_DAILY = Notification::DAILY;


    /**
     * @ORM\ManyToOne(targetEntity="Category")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Category
     */
    private $category;



    public function __construct(User $user, Category $category, int $notificationLevel = self::NOTIFICATIONS_OFF) {
        $this->user = $user;
        $this->category = $category;
        $this->notificationLevel = $notificationLevel;
    }

    public function getCategory() : Category {
        return $this->category;
    }

}
