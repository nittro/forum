<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicEditorControl;

use App\Entity\Category;
use App\Entity\Topic;


interface ITopicEditorControlFactory {

    public function create(Category $category, ?Topic $topic = null) : TopicEditorControl;

}
