<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Components\UserListControl\IUserListControlFactory;
use App\AdminModule\Components\UserListControl\UserListControl;
use App\ORM\Manager\UserManager;


class DashboardPresenter extends BasePresenter {

    private $userManager;

    private $userListControlFactory;


    public function __construct(
        UserManager $userManager,
        IUserListControlFactory $userListControlFactory
    ) {
        parent::__construct();
        $this->userManager = $userManager;
        $this->userListControlFactory = $userListControlFactory;
    }



    public function createComponentNewestUsers() : UserListControl {
        $users = $this->userManager->lookup()
            ->withAccounts()
            ->newestFirst();

        $control = $this->userListControlFactory->create($users);
        $control->disablePaging();
        $control->setPageSize(10);
        return $control;
    }

}
