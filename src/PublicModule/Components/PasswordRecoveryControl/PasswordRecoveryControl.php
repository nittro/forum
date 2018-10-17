<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PasswordRecoveryControl;

use App\Mail\Mailer;
use App\ORM\Manager\AccountManager;
use App\ORM\Manager\PasswordRequestManager;
use App\UI\BaseControl;
use App\UI\FormErrorHandlerTrait;


class PasswordRecoveryControl extends BaseControl {
    use FormErrorHandlerTrait;

    private $accountManager;

    private $passwordRequestManager;

    private $mailer;


    public function __construct(AccountManager $accountManager, PasswordRequestManager $passwordRequestManager, Mailer $mailer) {
        parent::__construct();
        $this->accountManager = $accountManager;
        $this->passwordRequestManager = $passwordRequestManager;
        $this->mailer = $mailer;
    }


    private function doSend(PasswordRecoveryForm $form, array $values) : void {
        $account = $this->accountManager->getByLoginOrEmail($values['identifier']);

        if ($account) {
            $request = $this->passwordRequestManager->createRequest($account);

            $this->mailer->send($account->getUser(), 'passwordResetRequest', [
                'user' => $account->getUser(),
                'request' => $request,
            ]);
        }

        $this->postGet('this');
        $this->redrawControl('form');
        $this->setView('sent');
    }


    public function createComponentForm() : PasswordRecoveryForm {
        $form = new PasswordRecoveryForm();
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doSend']);
        $form->onError[] = $this->getFormErrorHandler();
        return $form;
    }

}
