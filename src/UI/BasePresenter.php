<?php

declare(strict_types=1);

namespace App\UI;

use App\Entity\User;
use App\ORM\Manager\UserManager;
use Nextras\Application\UI\SecuredLinksPresenterTrait;
use Nittro\Bridges\NittroUI\Presenter;


abstract class BasePresenter extends Presenter {
    use SecuredLinksPresenterTrait;


    public function getSnippetId($name = null) {
        return $name ? ($name[0] === '#' ? '#' : '') . parent::getSnippetId(ltrim($name, '#')) : parent::getSnippetId(null);
    }

    public function getUserEntity() : ?User {
        return $this->context->getByType(UserManager::class)->getCurrentUser();
    }

    public function denyUnlessAuthorized(?string $role = null) : void {
        $this->denyUnlessTrue($role ? $this->getUser()->isInRole($role) : $this->getUser()->isLoggedIn());
    }

    public function denyUnlessTrue(bool $value) : void {
        if (!$value) {
            $this->error('Forbidden!', 403);
        }
    }

}
