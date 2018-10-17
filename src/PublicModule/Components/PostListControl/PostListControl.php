<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PostListControl;

use App\Entity\Post;
use App\Entity\Topic;
use App\ORM\Lookup\AbstractLookup;
use App\ORM\Lookup\PostLookup;
use App\ORM\Manager\PostManager;
use App\ORM\Manager\TopicSubscriptionManager;
use App\UI\BaseControl;
use App\UI\PagedControlTrait;


class PostListControl extends BaseControl {
    use PagedControlTrait;

    private $postManager;

    private $subscriptionManager;

    private $topic;


    /** @var PostLookup */
    private $postLookup = null;

    /** @var Post[] */
    private $posts = null;

    /** @var int */
    private $count = null;

    private $updateSubscription = true;



    public function __construct(PostManager $postManager, TopicSubscriptionManager $subscriptionManager, Topic $topic) {
        parent::__construct();
        $this->postManager = $postManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->topic = $topic;
    }

    protected function getResource() : AbstractLookup {
        return $this->postLookup ?? $this->postLookup = $this->postManager->lookup()->inTopic($this->topic);
    }

    private function getPosts() {
        return $this->posts ?? $this->posts = $this->getPagedResource()->toArray();
    }

    public function getItemSnippetName() : string {
        return 'post';
    }


    /**
     * @param int $id
     * @secured
     */
    public function handleDelete(int $id) : void {
        $post = $this->postManager->getPost($id);
        $this->denyUnlessAdminOrOwner($post->getAuthor());

        $this->postManager->deletePost($post);

        $this->postGet('this');
        $this->presenter->sendPayload();
    }


    public function postCreated(Post $post) : void {
        $this->redrawControl('list');
        $this->disablePaging();
        $this->posts[] = $post;
        $this->updateSubscription = false;
    }

    public function render() : void {
        $this->template->topic = $this->topic;
        $this->template->posts = $this->getPosts();

        $this->template->paging = [
            'first' => !$this->paging || !$this->getComponent('page')->hasPrevious(),
            'last' => !$this->paging || !$this->getComponent('page')->hasNext(),
        ];

        if ($this->paging) {
            $this->getComponent('page')->setButtonLabels('load previous replies', 'load more replies');
        }

        if ($this->updateSubscription && $this->posts) {
            $this->subscriptionManager->markAsRead(end($this->posts));
        }

        parent::render();
    }


}
