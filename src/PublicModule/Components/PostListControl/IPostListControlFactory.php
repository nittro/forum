<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PostListControl;

use App\Entity\Topic;


interface IPostListControlFactory {

    public function create(Topic $topic) : PostListControl;

}
