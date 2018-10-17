<?php

declare(strict_types=1);

namespace App\Mail;

use App\Entity\User;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Mail\IMailer;


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


    public function createBuilder(User $recipient, string $template) : Builder {
        return new Builder(\Closure::fromCallable([$this, 'createTemplate']), $recipient, $template);
    }


    public function send(Message $message) : void {
        $message->setFrom($this->sender);
        $message->setReturnPath($this->returnPath);
        $this->mailer->send($message);
    }


    private function createTemplate() : Template {
        /** @var Template $tpl */
        $tpl = $this->templateFactory->createTemplate();
        $tpl->getLatte()->addProvider('uiControl', $this->linkGenerator);
        return $tpl;
    }

}
