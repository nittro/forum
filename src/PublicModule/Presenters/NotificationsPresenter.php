<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\Entity\Notification;
use App\ORM\Manager\NotificationManager;
use Nette\Application\Responses\TextResponse;
use Nette\Http\IResponse;


class NotificationsPresenter extends BasePresenter {

    private $notificationManager;


    public function __construct(NotificationManager $notificationManager) {
        parent::__construct();
        $this->notificationManager = $notificationManager;
    }


    public function actionDefault(?int $since = null) : void {
        if (!$this->getUser()->isLoggedIn()) {
            $this->sendEmptyResponse(IResponse::S401_UNAUTHORIZED);
        }

        if ($notifications = $this->notificationManager->getNotificationsForCurrentUser($since)) {
            $this->sendJson(array_map(\Closure::fromCallable([$this, 'exportNotification']), $notifications));
        } else {
            $this->sendEmptyResponse();
        }
    }


    public function actionDismiss(?int $id = null) : void {
        if ($this->getUser()->isLoggedIn()) {
            $this->notificationManager->dismiss($id);
        }

        $this->sendEmptyResponse();
    }


    private function sendEmptyResponse(int $status = IResponse::S204_NO_CONTENT) : void {
        $this->getHttpResponse()->setCode($status);
        $this->sendResponse(new TextResponse(''));
    }


    private function exportNotification(Notification $notification) : array {
        $post = $notification->getPost();
        $author = $post->getAuthor();
        $topic = $post->getTopic();
        $category = $topic->getCategory();

        return [
            'id' => $notification->getId(),
            'url' => $this->getPresenter()->link('Topic:default', ['topic' => $topic, 'r' => $post->getId()]),
            'first' => $post->getId() === $topic->getFirstPost()->getId(),
            'posted_on' => $post->getPostedOn()->format('c'),
            'author_login' => $author->getLogin(),
            'author_name' => $author->getName(),
            'topic_title' => $topic->getTitle(),
            'category_name' => $category->getName(),
        ];
    }

}
