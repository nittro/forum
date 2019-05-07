<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\Mail;
use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Random;


class Builder {

    private $templateFactory;

    private $template;

    private $recipient;

    private $topic;

    private $post;

    private $token;

    private $params = [];

    private $headers = [];


    private $subject;

    private $body;

    private $htmlBody;


    public function __construct(callable $templateFactory, User $recipient, string $template) {
        $this->templateFactory = $templateFactory;
        $this->recipient = $recipient;
        $this->template = $template;
    }

    public function getTemplate() : string {
        return $this->template;
    }

    public function getRecipient() : User {
        return $this->recipient;
    }

    public function getTopic() : ?Topic {
        return $this->topic;
    }

    public function getPost() : ?Post {
        return $this->post;
    }

    public function getToken() : ?string {
        return $this->token;
    }

    public function getParams() : array {
        return $this->params + [
            'user' => $this->recipient,
            'topic' => $this->topic,
            'post' => $this->post,
        ];
    }

    public function getHeaders() : array {
        return $this->headers;
    }

    public function getSubject() : string {
        $this->buildSubject();
        return $this->subject;
    }

    public function getBody() : string {
        $this->buildBody();
        return $this->body;
    }

    public function getHtmlBody() : string {
        $this->buildHtmlBody();
        return $this->htmlBody;
    }



    public function buildMail() : Mail {
        return new Mail(
            $this->recipient,
            $this->topic,
            $this->post,
            $this->token,
            $this->recipient->getEmail()
        );
    }

    public function buildMessage() : Message {
        $message = new Message();
        $message->addTo($this->recipient->getEmail(), $this->recipient->getName());
        $message->setSubject($this->getSubject());
        $message->setBody($this->getBody());
        $message->setHtmlBody($this->getHtmlBody());

        foreach ($this->headers as $header => $value) {
            $message->setHeader($header, $value);
        }

        return $message;
    }



    public function setContext(Topic $topic, ?Post $post = null) : self {
        $this->topic = $topic;
        $this->post = $post;
        return $this;
    }

    public function useToken() : self {
        $this->token = Random::generate(32, '0-9a-zA-Z.');
        return $this;
    }

    public function setParams(array $params) : self {
        $this->params = $params + $this->params;
        return $this;
    }

    public function setParam(string $param, $value) : self {
        $this->params[$param] = $value;
        return $this;
    }

    public function setHeaders(array $headers) : self {
        $this->headers = $headers + $this->headers;
        return $this;
    }

    public function setHeader(string $header, string $value) : self {
        $this->headers[$header] = $value;
        return $this;
    }



    private function buildSubject() : void {
        if (!isset($this->subject)) {
            $this->subject = trim($this->buildTemplate('subject'));
        }
    }

    private function buildBody() : void {
        if (!isset($this->body)) {
            $this->body = $this->buildTemplate('txt');
        }
    }

    private function buildHtmlBody() : void {
        if (!isset($this->htmlBody)) {
            $this->htmlBody = $this->buildTemplate('html');
        }
    }

    private function buildTemplate(string $type) : string {
        /** @var Template $tpl */
        $tpl = call_user_func($this->templateFactory);
        $file = __DIR__ . sprintf('/templates/%s.%s.latte', $this->template, $type);
        return $tpl->renderToString($file, $this->getParams());
    }

}
