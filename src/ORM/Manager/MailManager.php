<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Mail;
use Kdyby\Doctrine\EntityManager;


class MailManager {

    private $em;

    private $mails;


    public function __construct(EntityManager $em) {
        $this->em = $em;
        $this->mails = $em->getRepository(Mail::class);
    }


    public function persistAndFlush(Mail $mail) : void {
        $this->em->persist($mail);
        $this->em->flush();
    }


    public function unsubscribe(int $msgid, string $token) : ?Mail {
        if (($mail = $this->mails->find($msgid)) && $mail->isValid($token)) {
            $mail->resetToken();
            $this->persistAndFlush($mail);
            return $mail;
        } else {
            return null;
        }
    }

}
