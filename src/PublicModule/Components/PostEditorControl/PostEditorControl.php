<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PostEditorControl;

use App\Entity\Post;
use App\Entity\Topic;
use App\ORM\Manager\TopicSubscriptionManager;
use App\ORM\Manager\UserManager;
use App\ORM\Manager\PostManager;
use App\ORM\Processor\ProcessorException;
use App\UI\BaseControl;
use App\UI\FormErrorHandlerTrait;
use Kdyby\Doctrine\EntityManager;


class PostEditorControl extends BaseControl {
    use FormErrorHandlerTrait;

    /** @var callable[] */
    public $onPostSaved = [];


    private $entityManager;

    private $postManager;

    private $userManager;

    private $subscriptionManager;

    private $topic;

    private $post;

    /** @var Post */
    private $replyingTo;

    private $inline = false;


    public function __construct(
        EntityManager $entityManager,
        PostManager $postManager,
        UserManager $userManager,
        TopicSubscriptionManager $subscriptionManager,
        Topic $topic,
        ?Post $post = null
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->postManager = $postManager;
        $this->userManager = $userManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->topic = $topic;
        $this->post = $post;
    }


    public function setInline(bool $inline = true) : void {
        $this->inline = $inline;
    }


    public function replyingTo(Post $post) : void {
        $this->replyingTo = $post;
    }


    public function render() : void {
        $this->template->inline = $this->inline;
        $this->template->editing = isset($this->post);
        $this->template->post = $this->post;
        parent::render();
    }


    public function handleSuggestUser(?string $login = null) : void {
        $suggested = $this->userManager->suggestByLogin($login, $this->topic);
        $this->presenter->sendJson($suggested);
    }


    private function doSavePost(PostForm $form, array $values) : void {
        if ($this->post) {
            try {
                $this->postManager->savePostEdit($this->post, $values['text']);
            } catch (ProcessorException $e) {
                $form['text']->addError($e->getMessage());
                $this->redrawControl('form');
                return;
            }
        } else {
            $this->entityManager->beginTransaction();

            try {
                $this->post = $this->postManager->createPost($this->topic, $this->userManager->getCurrentUser(), $values['text']);
                $this->subscriptionManager->markAsRead($this->post);

                if (!empty($values['subscribe'])) {
                    $this->subscriptionManager->subscribe($this->topic);
                } else {
                    $this->subscriptionManager->unsubscribe($this->topic);
                }

                $this->entityManager->commit();
            } catch (ProcessorException $e) {
                $this->entityManager->rollback();
                $form['text']->addError($e->getMessage());
                $this->redrawControl('form');
                return;
            } catch (\Throwable $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        }

        $this->onPostSaved($this->post);
    }


    public function createComponentForm() : PostForm {
        $form = new PostForm();
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doSavePost']);
        $form->onError[] = $this->getFormErrorHandler();

        $subscription = $this->subscriptionManager->getSubscription($this->topic);

        $form->setDefaults([
            'subscribe' => (bool) $subscription->getNotificationLevel()
        ]);

        if ($this->post) {
            $form->setDefaults([
                'text' => $this->post->getTextSource(),
            ]);
        } else if ($this->replyingTo) {
            $form->setDefaults([
                'text' => $this->formatQuote($this->replyingTo),
            ]);
        }

        return $form;
    }

    private function formatQuote(Post $post) : string {
        return sprintf(
            "> @%s wrote:\n> \n%s\n\n",
            $post->author->login,
            preg_replace('/^/m', '> ', $post->textSource)
        );
    }

}
