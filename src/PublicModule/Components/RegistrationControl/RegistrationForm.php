<?php

declare(strict_types=1);

namespace App\PublicModule\Components\RegistrationControl;

use App\Security\Helpers;
use Nette\Application\UI\Form;


class RegistrationForm extends Form {

    public function __construct() {
        parent::__construct();

        $this->addText('login')
            ->setRequired()
            ->addRule(
                Form::PATTERN_ICASE,
                'The specified login doesn\'t meet the required criteria:',
                Helpers::getLoginPattern()
            );

        $this->addPassword('password')
            ->setRequired()
            ->addRule(
                Form::PATTERN,
                'The password doesn\'t satisfy the security requirements:',
                Helpers::getPasswordPattern()
            );

        $this->addPassword('password_check')
            ->setRequired()
            ->addRule(Form::EQUAL, 'Passwords don\'t match', $this['password']);

        $this->addText('name')
            ->setRequired();

        $this->addEmail('email')
            ->setRequired();

        $this->addUpload('avatar')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE);

        $this->addCheckbox('subscribe_mentions')
            ->setDefaultValue(true);

        $this->addCheckbox('subscribe_auto_categories')
            ->setDefaultValue(true);

        $this->addProtection();

        $this->addSubmit('register');
    }

}
