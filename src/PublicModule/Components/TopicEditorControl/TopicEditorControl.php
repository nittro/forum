<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicEditorControl;

use App\Entity\Category;
use App\Entity\Topic;
use App\ORM\Manager\TopicManager;
use App\ORM\Manager\TopicSubscriptionManager;
use App\ORM\Manager\UserManager;
use App\ORM\Processor\ProcessorException;
use App\UI\BaseControl;
use App\UI\FormErrorHandlerTrait;
use Kdyby\Doctrine\EntityManager;


class TopicEditorControl extends BaseControl {
    use FormErrorHandlerTrait;

    /** @var callable[] */
    public $onTopicSaved = [];


    private $entityManager;

    private $topicManager;

    private $userManager;

    private $subscriptionManager;

    private $category;

    private $topic;



    public function __construct(
        EntityManager $entityManager,
        TopicManager $topicManager,
        UserManager $userManager,
        TopicSubscriptionManager $subscriptionManager,
        Category $category,
        ?Topic $topic = null
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->topicManager = $topicManager;
        $this->userManager = $userManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->category = $category;
        $this->topic = $topic;
    }


    public function render() : void {
        $this->template->topic = $this->topic;
        $this->template->category = $this->category;

        if ($this->topic) {
            $this->template->cancel = $this->getPresenter()->link('Topic:default', [$this->topic]);
        } else {
            $this->template->cancel = $this->getPresenter()->link('Category:default', [$this->category]);
        }

        parent::render();
    }


    public function handleSuggestUser(?string $login = null) : void {
        $suggested = $this->userManager->suggestByLogin($login, $this->topic);
        $this->presenter->sendJson($suggested);
    }




    private function doSave(TopicForm $form, array $values) : void {
        try {
            if ($this->topic) {
                $this->topicManager->updateTopic($this->topic, $values['title'], $values['text']);
            } else {
                $this->topic = $this->topicManager->createTopic(
                    $this->category,
                    $this->userManager->getCurrentUser(),
                    $values['title'],
                    $values['text']
                );

                if (!empty($values['subscribe'])) {
                    $this->subscriptionManager->subscribe($this->topic);
                }
            }

            $this->onTopicSaved($this->topic);
        } catch (ProcessorException $e) {
            $form->getComponent('text')->addError($e->getMessage());
            $this->redrawControl('form');
        }
    }


    public function createComponentForm() : TopicForm {
        $form = new TopicForm(!isset($this->topic));
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doSave']);
        $form->onError[] = $this->getFormErrorHandler();

        if ($this->topic) {
            $form->setDefaults([
                'title' => $this->topic->getTitle(),
                'text' => $this->topic->getFirstPost()->getTextSource(),
            ]);
        }

        return $form;
    }

}
