<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PasswordRecoveryControl;

use App\Security\Helpers;
use Nette\Application\UI\Form;


class PasswordRecoveryForm extends Form {

    public function __construct() {
        parent::__construct();

        $this->addText('identifier')
            ->setRequired()
            ->addCondition(~Form::EMAIL)
            ->addRule(
                Form::PATTERN,
                'Please provide a valid login or e-mail address',
                Helpers::getLoginPattern()
            );

        $this->addProtection();

        $this->addSubmit('send');
    }

}
