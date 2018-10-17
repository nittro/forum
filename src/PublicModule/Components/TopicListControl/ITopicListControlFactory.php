<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicListControl;

use App\ORM\Lookup\TopicLookup;


interface ITopicListControlFactory {

    public function create(TopicLookup $topics) : TopicListControl;

}
