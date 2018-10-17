<?php

declare(strict_types=1);

namespace App\UI;

use App\Entity\User;
use Nette\Application\UI\Control;
use Nextras\Application\UI\SecuredLinksControlTrait;
use Nittro\Bridges\NittroUI\ComponentUtils;


abstract class BaseControl extends Control {
    use ComponentUtils, SecuredLinksControlTrait;

    private $view = 'default';


    protected function setView(string $view) : void {
        $this->view = $view;
    }

    protected function getView() : string {
        return $this->view;
    }


    public function render() : void {
        if (!$this->getTemplate()->getFile()) {
            $rc = new \ReflectionClass($this);
            $basepath = dirname($rc->getFileName());
            $this->getTemplate()->setFile($basepath . '/templates/' . $this->getView() . '.latte');
        }

        $this->getTemplate()->render();
    }

    public function denyUnlessAuthorized(?string $role = null) : void {
        $this->getPresenter()->denyUnlessAuthorized($role);
    }

    public function denyUnlessAdminOrOwner(User $owner) : void {
        $user = $this->getPresenter()->getUser();
        $this->getPresenter()->denyUnlessTrue($user->isInRole('admin') || $user->getId() === $owner->getId());
    }

    public function getSnippetId($name = null) {
        return $name ? ($name[0] === '#' ? '#' : '') . parent::getSnippetId(ltrim($name, '#')) : parent::getSnippetId(null);
    }

}
