<?php

declare(strict_types=1);

namespace App\PublicModule\Components\TopicEditorControl;

use Nette\Application\UI\Form;


class TopicForm extends Form {

    public function __construct(bool $withSubscription = true) {
        parent::__construct();

        $this->addText('title')
            ->setRequired();

        $this->addTextArea('text')
            ->setRequired();

        if ($withSubscription) {
            $this->addCheckbox('subscribe')
                ->setDefaultValue(true);
        }

        $this->addProtection();
        $this->addSubmit('save');
    }

}
