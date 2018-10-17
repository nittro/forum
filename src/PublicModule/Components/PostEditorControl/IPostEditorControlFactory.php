<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PostEditorControl;

use App\Entity\Post;
use App\Entity\Topic;


interface IPostEditorControlFactory {

    public function create(Topic $topic, ?Post $post = null) : PostEditorControl;

}
