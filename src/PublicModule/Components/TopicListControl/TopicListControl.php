<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicListControl;

use App\ORM\Lookup\AbstractLookup;
use App\ORM\Lookup\TopicLookup;
use App\ORM\Manager\PostManager;
use App\ORM\Manager\TopicSubscriptionManager;
use App\UI\BaseControl;
use App\UI\PagedControlTrait;
use Nette\Security\User;


class TopicListControl extends BaseControl {
    use PagedControlTrait;

    public const MODE_LATEST = 'latest',
        MODE_DEFAULT = 'default';

    private $user;

    private $postManager;

    private $subscriptionManager;

    private $topics;


    private $mode = self::MODE_DEFAULT;

    private $showUnread = false;

    /** @var array */
    private $replyCounts = null;

    /** @var array */
    private $unreadCounts = null;


    public function __construct(User $user, PostManager $postManager, TopicSubscriptionManager $subscriptionManager, TopicLookup $topics) {
        parent::__construct();
        $this->user = $user;
        $this->postManager = $postManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->topics = $topics;
    }

    public function setMode(string $mode) : void {
        $this->mode = $mode;
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

    public function getItemSnippetName() : string {
        return 'topic';
    }

    private function getReplyCounts() : array {
        if (!isset($this->replyCounts)) {
            $postCounts = $this->postManager->getCountPerTopic($this->getPagedResource()->extract('id'));
            $this->replyCounts = array_map(function(int $n) { return $n - 1; }, $postCounts);
        }

        return $this->replyCounts;
    }

    private function getUnreadCounts() : array {
        if (!isset($this->unreadCounts)) {
            $this->unreadCounts = $this->subscriptionManager->getUnreadRepliesPerTopic($this->getPagedResource()->extract('id'));
        }

        return $this->unreadCounts;
    }


    public function render() : void {
        $this->template->mode = $this->mode;
        $this->template->topics = $this->getPagedResource();
        $this->template->replyCounts = $this->getReplyCounts();
        $this->template->unreadCounts = $this->showUnread ? $this->getUnreadCounts() : null;

        $this->template->paging = [
            'first' => !$this->paging || !$this->getComponent('page')->hasPrevious(),
            'last' => !$this->paging || !$this->getComponent('page')->hasNext(),
        ];

        if ($this->paging) {
            $this->getComponent('page')->setButtonLabels('load previous topics', 'load more topics');
        }

        parent::render();
    }

}
