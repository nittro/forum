<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicListControl;

use App\ORM\Lookup\AbstractLookup;
use App\ORM\Lookup\TopicLookup;
use App\UI\BaseControl;
use App\UI\PagedControlTrait;


class TopicListControl extends BaseControl {
    use PagedControlTrait;


    private $topics;


    private $showUnread = false;



    public function __construct(TopicLookup $topics) {
        parent::__construct();
        $this->topics = $topics;
        $this->setItemSnippetName('topic');
    }

    public function showUnread() : void {
        $this->showUnread = true;
    }

    public function hasContent() : bool {
        return $this->topics->getTotalCount() > 0;
    }

    protected function getResource() : AbstractLookup {
        return $this->topics;
    }


    public function render() : void {
        $this->template->topics = $this->getPagedResource();
        $this->template->showUnread = $this->showUnread;
        $this->setupPaging($this->template, 'load previous topics', 'load more topics');
        parent::render();
    }

}
