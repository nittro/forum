<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\Entity\User;
use App\ORM\Manager\UserManager;


class ProfilePresenter extends BasePresenter {

    private $userManager;


    public function __construct(UserManager $userManager) {
        parent::__construct();
        $this->userManager = $userManager;
    }


    public function renderDefault(User $user) : void {
        $this->template->profile = $user;
    }

}
