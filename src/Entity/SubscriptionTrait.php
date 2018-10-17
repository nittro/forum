<?php

declare(strict_types=1);

namespace App\Entity;


trait SubscriptionTrait {

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
     * @ORM\Column(name="notification_level", type="smallint")
     * @var int
     */
    private $notificationLevel;


    public function getId() : int {
        return $this->id;
    }

    public function getUser() : User {
        return $this->user;
    }

    public function getNotificationLevel() : int {
        return $this->notificationLevel;
    }


    public function setNotificationLevel(int $level) : void {
        $this->notificationLevel = $level;
    }

}
