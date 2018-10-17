<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\Entity\Post;
use App\Entity\Topic;
use App\PublicModule\Components\PostEditorControl\IPostEditorControlFactory;
use App\PublicModule\Components\PostEditorControl\PostEditorControl;


class PostPresenter extends BasePresenter {

    private $postManager;

    private $postEditorControlFactory;


    /** @var Topic */
    private $topic;

    /** @var Post */
    private $edit;

    /** @var Post */
    private $replyTo;



    public function __construct(
        IPostEditorControlFactory $postEditorControlFactory
    ) {
        parent::__construct();
        $this->postEditorControlFactory = $postEditorControlFactory;
    }


    public function actionEdit(Post $post) : void {
        $this->topic = $post->getTopic();
        $this->edit = $post;
        $this->setView('@form');
        $this->template->topic = $this->topic;
    }

    public function actionReply(Post $post) : void {
        $this->topic = $post->getTopic();
        $this->replyTo = $post;
        $this->setView('@form');
        $this->template->topic = $this->topic;
    }


    private function postSaved(Post $post) : void {
        $this->redirect('Topic:default', [
            'topic' => $this->topic,
            'r' => $post->getId(),
        ]);
    }


    public function createComponentPost() : PostEditorControl {
        if (isset($this->edit)) {
            $this->denyUnlessTrue($this->getUser()->isInRole('admin') || $this->getUser()->getId() === $this->edit->getAuthor()->getId());
        }

        $control = $this->postEditorControlFactory->create($this->topic, $this->edit);
        $control->onPostSaved[] = \Closure::fromCallable([$this, 'postSaved']);

        if ($this->replyTo) {
            $control->replyingTo($this->replyTo);
        }

        return $control;
    }
}
