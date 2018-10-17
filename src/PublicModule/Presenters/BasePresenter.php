<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\PublicModule\Components\CategoryListControl\CategoryListControl;
use App\PublicModule\Components\CategoryListControl\ICategoryListControlFactory;
use App\PublicModule\Components\NotificationsControl\INotificationsControlFactory;
use App\PublicModule\Components\NotificationsControl\NotificationsControl;
use App\PublicModule\Components\UserControl\IUserControlFactory;
use App\PublicModule\Components\UserControl\UserControl;
use App\UI\BasePresenter as CommonBasePresenter;
use Nette\Application\Request;


abstract class BasePresenter extends CommonBasePresenter {

    protected $title = 'Nittro Forums';

    protected function startup() {
        parent::startup();

        $this->setDefaultSnippets(['header', 'main']);

        if ($this->isAjax()) {
            if ($this->getRequest()->hasFlag(Request::RESTORED) || $this->getRequest()->isMethod(Request::FORWARD)) {
                $this->postGet('this');
            }

            if ($this->getHttpRequest()->getHeader('X-Redraw') === 'full') {
                $this->setDefaultSnippets(['header', 'content']);
            }
        }
    }

    protected function afterRender() : void {
        parent::afterRender();
        $this->template->title = $this->payload->title = $this->title;

        if (!isset($this->payload->scrollTo) && ($at = $this->getParameter('at')) !== null) {
            if (is_numeric($at)) {
                $this->payload->scrollTo = (int) $at;
            } else if ($at[0] !== '.' && $at[0] !== '#') {
                $this->payload->scrollTo = '#' . $this->getSnippetId($at);
            } else {
                $this->payload->scrollTo = $at;
            }
        }
    }


    public function createComponentUserPanel() : UserControl {
        return $this->context->getByType(IUserControlFactory::class)->create();
    }


    public function createComponentCategoryList() : CategoryListControl {
        return $this->context->getByType(ICategoryListControlFactory::class)->create();
    }


    public function createComponentNotifications() : NotificationsControl {
        return $this->context->getByType(INotificationsControlFactory::class)->create();
    }

}
