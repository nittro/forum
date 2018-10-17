<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\UI\BasePresenter as CommonBasePresenter;
use Nette\Application\Request;


abstract class BasePresenter extends CommonBasePresenter {

    protected $module;


    protected function startup() : void {
        parent::startup();
        $this->setDefaultSnippets(['header', 'content']);

        if (!$this->isPublic() && (!$this->getUser()->isLoggedIn() || !$this->getUser()->isInRole('admin'))) {
            $this->disallowAjax()->redirect(':Public:User:login', [
                'redir' => $this->link('this'),
            ]);
        }
    }


    protected function afterRender() : void {
        parent::afterRender();

        if (!isset($this->module)) {
            $this->module = lcfirst(preg_replace('/^Admin:/', '', $this->getName()));
        }

        $this->template->module = $this->payload->module = $this->module;

        if ($this->isAjax() && ($this->getRequest()->hasFlag(Request::RESTORED) || $this->getRequest()->isMethod(Request::FORWARD))) {
            $this->postGet('this');
        }
    }


    protected function isPublic() : bool {
        return false;
    }

}
