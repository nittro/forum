<?php

declare(strict_types=1);

namespace App\Mail;

use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Mail\IMailer;
use Nette\Mail\Message;


class Mailer {

    private $mailer;

    private $templateFactory;

    private $linkGenerator;

    private $sender;

    private $returnPath;


    public function __construct(
        IMailer $mailer,
        ITemplateFactory $templateFactory,
        LinkGenerator $linkGenerator,
        string $sender,
        string $returnPath
    ) {
        $this->mailer = $mailer;
        $this->templateFactory = $templateFactory;
        $this->linkGenerator = $linkGenerator;
        $this->sender = $sender;
        $this->returnPath = $returnPath;
    }


    /**
     * @param object|array|string $recipient
     * @param ITemplate|string $template
     * @param array $params
     * @param array $headers
     */
    public function send($recipient, $template, array $params = [], array $headers = []) : void {
        $message = new Message();
        $message->setFrom($this->sender);
        $message->setReturnPath($this->returnPath);

        foreach ($headers as $header => $value) {
            $message->setHeader($header, $value);
        }

        if (is_string($recipient)) {
            $message->addTo($recipient);
        } else if (is_array($recipient)) {
            if (isset($recipient['email'])) {
                $message->addTo($recipient['email'], $recipient['name'] ?? null);
            } else {
                @list($email, $name) = array_values($recipient);
                $message->addTo($email, $name);
            }
        } else if (is_object($recipient) && isset($recipient->email)) {
            $message->addTo($recipient->email, $recipient->name ?? null);
        } else {
            throw new \InvalidArgumentException("Invalid recipient, expected a string, an array or an object, got " . gettype($recipient));
        }

        if (!($template instanceof ITemplate)) {
            $template = $this->createTemplate($template);
            $template->setParameters($params);
        }

        $message->setHtmlBody($template);
        $this->mailer->send($message);
    }


    private function createTemplate(string $template) : Template {
        /** @var Template $tpl */
        $tpl = $this->templateFactory->createTemplate();
        $tpl->setFile(__DIR__ . '/templates/' . $template . '.latte');
        $tpl->getLatte()->addProvider('uiControl', $this->linkGenerator);
        return $tpl;
    }

}
