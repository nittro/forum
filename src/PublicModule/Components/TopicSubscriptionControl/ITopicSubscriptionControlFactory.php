<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicSubscriptionControl;

use App\Entity\Topic;


interface ITopicSubscriptionControlFactory {

    public function create(Topic $topic) : TopicSubscriptionControl;

}
