<?php

declare(strict_types=1);

namespace App\AdminModule\Components\UserListControl;

use App\ORM\Lookup\UserLookup;


interface IUserListControlFactory {

    public function create(UserLookup $users) : UserListControl;

}
