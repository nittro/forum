<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\ORM\Manager\TopicManager;
use App\PublicModule\Components\TopicListControl\ITopicListControlFactory;
use App\PublicModule\Components\TopicListControl\TopicListControl;


class HomePresenter extends BasePresenter {

    private $topicManager;

    private $topicListControlFactory;

    public function __construct(
        TopicManager $topicManager,
        ITopicListControlFactory $topicListControlFactory
    ) {
        parent::__construct();
        $this->topicManager = $topicManager;
        $this->topicListControlFactory = $topicListControlFactory;
    }


    public function renderDefault() : void {
        $this->template->showUnread = $this->getUser()->isLoggedIn() && $this->getComponent('unread')->hasContent();
    }


    public function createComponentUnread() : TopicListControl {
        $topics = $this->topicManager->lookup()
            ->withCategory()
            ->withLastPost()
            ->unreadBy($this->getUser()->getId());

        $control = $this->topicListControlFactory->create($topics);
        $control->setMode(TopicListControl::MODE_LATEST);
        $control->showUnread();
        $control->disablePaging();
        $control->setPageSize(10);
        return $control;
    }

    public function createComponentRecent() : TopicListControl {
        $topics = $this->topicManager->lookup()
            ->withCategory()
            ->withLastPost();

        $control = $this->topicListControlFactory->create($topics);
        $control->setMode(TopicListControl::MODE_LATEST);
        $control->disablePaging();
        $control->setPageSize(10);

        if ($this->getUser()->isLoggedIn()) {
            $topics->readOrUnsubscribedBy($this->getUser()->getId());
        }

        return $control;
    }

}
