<?php

declare(strict_types=1);

namespace App\PublicModule\Components\SettingsControl;

use Nette\Application\UI\Form;


class SettingsForm extends Form {

    public function __construct(bool $requirePassword = true) {
        parent::__construct();

        if ($requirePassword) {
            $this->addPassword('password')
                ->setRequired();
        }

        $this->addText('name')
            ->setRequired();

        $this->addEmail('email')
            ->setRequired();

        $this->addUpload('avatar')
            ->addCondition(Form::FILLED)
            ->addRule(Form::IMAGE);

        $this->addCheckbox('subscribe_mentions');

        $this->addProtection();

        $this->addSubmit('save');
    }

}
