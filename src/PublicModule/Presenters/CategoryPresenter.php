<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\Entity\Category;
use App\ORM\Manager\TopicSubscriptionManager;
use App\PublicModule\Components\TopicListControl\ITopicListControlFactory;
use App\PublicModule\Components\TopicListControl\TopicListControl;
use App\ORM\Manager\TopicManager;


class CategoryPresenter extends BasePresenter {

    private $topicListControlFactory;

    private $topicManager;

    private $topicSubscriptionManager;

    private $category;


    public function __construct(
        ITopicListControlFactory $topicListControlFactory,
        TopicManager $topicManager,
        TopicSubscriptionManager $topicSubscriptionManager
    ) {
        parent::__construct();
        $this->topicListControlFactory = $topicListControlFactory;
        $this->topicManager = $topicManager;
        $this->topicSubscriptionManager = $topicSubscriptionManager;
    }


    protected function startup() : void {
        parent::startup();

        $this->category = $this->getParameter('category');

        if (!$this->category || !($this->category instanceof Category)) {
            $this->error();
        }
    }


    public function actionPermalink(Category $category) : void {
        $this->redirectPermanent('default', [$category]);
    }


    public function renderDefault(Category $category) : void {
        $this->template->category = $this->category;

        if ($this->isAjax() && empty($this->payload->postGet)) {
            $this->postGet('this');
        }
    }


    public function createComponentTopics() : TopicListControl {
        $topics = $this->topicManager->lookup()
            ->inCategory($this->category)
            ->withLastPost()
            ->withUnreadCounts($this->getUser()->getId());

        $control = $this->topicListControlFactory->create($topics);

        if ($this->getUser()->isLoggedIn()) {
            $control->showUnread();
        }

        return $control;
    }

}
