<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\ORM\Manager\MailManager;
use App\ORM\Manager\TopicSubscriptionManager;


class MailPresenter extends BasePresenter {

    private $mailManager;

    private $topicSubscriptionManager;


    public function __construct(MailManager $mailManager, TopicSubscriptionManager $topicSubscriptionManager) {
        parent::__construct();
        $this->mailManager = $mailManager;
        $this->topicSubscriptionManager = $topicSubscriptionManager;
    }


    public function renderUnsubscribe(int $msgid, string $token) : void {
        if ($msg = $this->mailManager->unsubscribe($msgid, $token)) {
            $this->topicSubscriptionManager->unsubscribeUser($msg->getRecipient(), $msg->getTopic());
            $this->template->topic = $msg->getTopic();
        } else {
            $this->setView('@invalid');
        }
    }

}
