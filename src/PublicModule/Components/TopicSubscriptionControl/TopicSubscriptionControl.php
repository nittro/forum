<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicSubscriptionControl;

use App\Entity\Topic;
use App\Entity\TopicSubscription;
use App\ORM\Manager\TopicSubscriptionManager;
use App\UI\BaseControl;


class TopicSubscriptionControl extends BaseControl {

    private $subscriptionManager;

    private $topic;


    public function __construct(TopicSubscriptionManager $subscriptionManager, Topic $topic) {
        parent::__construct();
        $this->subscriptionManager = $subscriptionManager;
        $this->topic = $topic;
    }


    public function render() : void {
        $subscription = $this->subscriptionManager->getSubscription($this->topic);
        $this->template->subscribed = $subscription->getNotificationLevel() !== TopicSubscription::NOTIFICATIONS_OFF;
        parent::render();
    }


    /**
     * @param int|null $on
     * @secured
     */
    public function handleToggle(?int $on = null) : void {
        if ($on) {
            $this->subscriptionManager->subscribe($this->topic);
        } else {
            $this->subscriptionManager->unsubscribe($this->topic);
        }

        $this->postGet('this');
        $this->redrawControl('setting');
    }

}
