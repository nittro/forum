<?php

declare(strict_types=1);

namespace App\Mail;


class Message extends \Nette\Mail\Message {

    protected function build()
    {
        $mail = parent::build();
        $mail->setHeader('Message-ID', $this->getHeader('Message-ID'));
        return $mail;
    }

}
