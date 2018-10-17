<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PostEditorControl;

use Nette\Application\UI\Form;


class PostForm extends Form {

    public function __construct() {
        parent::__construct();

        $this->addTextArea('text')
            ->setRequired();

        $this->addCheckbox('subscribe');

        $this->addProtection();
        $this->addSubmit('save');
    }

}
