<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\Entity\Category;
use App\Entity\Post;
use App\Entity\Topic;
use App\ORM\Manager\CategoryManager;
use App\ORM\Manager\PostManager;
use App\ORM\Manager\TopicSubscriptionManager;
use App\PublicModule\Components\PostEditorControl\IPostEditorControlFactory;
use App\PublicModule\Components\PostEditorControl\PostEditorControl;
use App\PublicModule\Components\PostListControl\PostListControl;
use App\PublicModule\Components\PostListControl\IPostListControlFactory;
use App\ORM\Manager\TopicManager;
use App\PublicModule\Components\TopicEditorControl\ITopicEditorControlFactory;
use App\PublicModule\Components\TopicEditorControl\TopicEditorControl;
use App\PublicModule\Components\TopicSubscriptionControl\ITopicSubscriptionControlFactory;
use App\PublicModule\Components\TopicSubscriptionControl\TopicSubscriptionControl;
use Doctrine\ORM\NoResultException;


class TopicPresenter extends BasePresenter {

    private const PAGE_SIZE = 20;

    private $categoryManager;

    private $topicManager;

    private $postManager;

    private $topicSubscriptionManager;

    private $postListControlFactory;

    private $postEditorControlFactory;

    private $topicEditorControlFactory;

    private $topicNotificationSettingControlFactory;



    /** @var Topic */
    private $topic;

    /** @var Category */
    private $category;


    public function __construct(
        CategoryManager $categoryManager,
        TopicManager $topicManager,
        PostManager $postManager,
        TopicSubscriptionManager $topicSubscriptionManager,
        IPostListControlFactory $postListControlFactory,
        IPostEditorControlFactory $postEditorControlFactory,
        ITopicEditorControlFactory $topicEditorControlFactory,
        ITopicSubscriptionControlFactory $topicNotificationSettingControlFactory
    ) {
        parent::__construct();
        $this->categoryManager = $categoryManager;
        $this->topicManager = $topicManager;
        $this->postManager = $postManager;
        $this->topicSubscriptionManager = $topicSubscriptionManager;
        $this->postListControlFactory = $postListControlFactory;
        $this->postEditorControlFactory = $postEditorControlFactory;
        $this->topicEditorControlFactory = $topicEditorControlFactory;
        $this->topicNotificationSettingControlFactory = $topicNotificationSettingControlFactory;
    }


    protected function startup() : void {
        parent::startup();

        if ($this->getAction() !== 'new') {
            $this->topic = $this->getParameter('topic');

            if (!$this->topic || !($this->topic instanceof Topic)) {
                $this->error();
            }
        }
    }


    public function actionPermalink(Topic $topic) : void {
        $this->redirectPermanent('default', [$topic]);
    }

    public function actionNew(int $category) : void {
        try {
            $this->category = $this->categoryManager->getById($category);
        } catch (NoResultException $e) {
            $this->error();
        }
    }

    public function actionEdit(Topic $topic) : void {
        $this->category = $topic->getCategory();
    }

    /**
     * @secured
     */
    public function handleDelete() : void {
        $this->denyUnlessTrue($this->getUser()->isInRole('admin') || $this->getUser()->getId() === $this->topic->author->id);
        $this->topicManager->deleteTopic($this->topic);
        $this->flashMessage('Topic has been deleted.');
        $this->redirect('Category:default', [$this->topic->getCategory()]);
    }


    public function renderDefault(Topic $topic, int $p = 1, ?int $r = null, ?string $at = null) : void {
        $this->template->topic = $this->topic;
        $this->template->subscription = $this->getUser()->isLoggedIn()
            ? $this->topicSubscriptionManager->getSubscription($this->topic)
            : null;

        $p0 = $p;
        $r0 = $r;

        if ($this->getUser()->isLoggedIn() && $at === 'unread') {
            $r = $this->topicSubscriptionManager->getFirstUnreadPost($this->topic);
        }

        if (isset($r)) {
            $info = $this->postManager->resolvePost($this->topic, $r);

            if ($info) {
                $p = (int) floor($info['idx'] / self::PAGE_SIZE) + 1;
                $r = $info['id'];
                $this->getComponent('posts')->setPage($p);
                $this->payload->scrollTo = '#r' . $r;
            } else {
                $r = null;
            }
        }

        if ($at || $p !== $p0 || $r !== $r0) {
            $this->postGet('this', [
                'p' => $p > 1 ? $p : null,
                'r' => $this->isAjax() ? null : $r,
                'at' => null,
            ]);
        }
    }

    public function renderNew() : void {
        $this->template->category = $this->category;
    }

    public function renderEdit() : void {
        $this->template->topic = $this->topic;
    }


    private function postSaved(Post $post) : void {
        $this->postGet('this');
        $this->getComponent('posts')->postCreated($post);
        $this->payload->scrollTo = '#r' . $post->getId();
    }

    private function topicSaved(Topic $topic) : void {
        $this->postGet('default', [$topic]);
        $this->setView('default');
        $this->setRedrawDefault();
        $this->topic = $topic;
    }


    public function createComponentPost() : PostEditorControl {
        $this->denyUnlessAuthorized();
        $control = $this->postEditorControlFactory->create($this->topic);
        $control->setInline();
        $control->onPostSaved[] = \Closure::fromCallable([$this, 'postSaved']);
        return $control;
    }


    public function createComponentPosts() : PostListControl {
        $control = $this->postListControlFactory->create($this->topic);
        $control->setPageSize(self::PAGE_SIZE);
        $control->setPage((int) $this->getParameter('p', 1));
        return $control;
    }

    public function createComponentSubscription() : TopicSubscriptionControl {
        $this->denyUnlessAuthorized();
        return $this->topicNotificationSettingControlFactory->create($this->topic);
    }


    public function createComponentTopic() : TopicEditorControl {
        if ($this->topic) {
            $this->denyUnlessTrue($this->getUser()->isInRole('admin') || $this->topic->getAuthor()->getId() === $this->getUser()->getId());
        } else {
            $this->denyUnlessAuthorized();
        }

        $control = $this->topicEditorControlFactory->create($this->category, $this->topic);
        $control->onTopicSaved[] = \Closure::fromCallable([$this, 'topicSaved']);
        return $control;
    }

}
