<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PasswordControl;

use App\Security\Helpers;
use Nette\Application\UI\Form;


class PasswordForm extends Form {

    public function __construct(bool $withCurrent = true) {
        parent::__construct();

        if ($withCurrent) {
            $this->addPassword('current_password')
                ->setRequired();
        }

        $this->addPassword('new_password')
            ->setRequired()
            ->addRule(
                Form::PATTERN,
                'The password doesn\'t satisfy the security requirements:',
                Helpers::getPasswordPattern()
            );

        $this->addPassword('new_password_check')
            ->setRequired()
            ->addRule(Form::EQUAL, 'Passwords don\'t match', $this['new_password']);

        $this->addProtection();

        $this->addSubmit('save');
    }

}
