<?php

declare(strict_types=1);

namespace App\PublicModule\Components\UserControl;

use App\UI\BaseControl;
use Nette\Security\User;


class UserControl extends BaseControl {

    private $user;


    public function __construct(User $user) {
        parent::__construct();
        $this->user = $user;
    }


    public function render() : void {
        $this->template->user = $this->user;
        parent::render();
    }

}
