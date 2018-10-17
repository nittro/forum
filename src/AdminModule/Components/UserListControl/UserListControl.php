<?php

declare(strict_types=1);

namespace App\AdminModule\Components\UserListControl;

use App\ORM\Lookup\AbstractLookup;
use App\ORM\Lookup\UserLookup;
use App\UI\BaseControl;
use App\UI\PagedControlTrait;


class UserListControl extends BaseControl {
    use PagedControlTrait;

    private $users;


    public function __construct(UserLookup $users) {
        parent::__construct();
        $this->users = $users;
        $this->setItemSnippetName('user');
    }

    protected function getResource() : AbstractLookup {
        return $this->users;
    }

    public function render() : void {
        $this->template->users = $this->getPagedResource();
        $this->setupPaging($this->template, 'previous', 'more');
        parent::render();
    }


}
