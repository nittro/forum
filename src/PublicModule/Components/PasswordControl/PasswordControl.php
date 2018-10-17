<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PasswordControl;

use App\ORM\Manager\AccountManager;
use App\Security\PrivilegeElevationManager;
use App\UI\BaseControl;
use App\UI\FormErrorHandlerTrait;
use Nette\Security\User;


class PasswordControl extends BaseControl {
    use FormErrorHandlerTrait;


    /** @var callable[] */
    public $onPasswordChanged = [];

    private $accountManager;

    private $privilegeManager;

    private $user;


    public function __construct(AccountManager $accountManager, PrivilegeElevationManager $privilegeManager, User $user) {
        parent::__construct();
        $this->accountManager = $accountManager;
        $this->privilegeManager = $privilegeManager;
        $this->user = $user;
    }


    public function render() : void {
        $this->template->requireCurrent = !$this->privilegeManager->arePrivilegesElevated();
        $this->template->history = (bool) count($this->onPasswordChanged);
        parent::render();
    }


    private function doChangePassword(PasswordForm $form, array $values) : void {
        $account = $this->accountManager->getById($this->user->getId());

        if (!$this->privilegeManager->arePrivilegesElevated()) {
            if (!$account->isPasswordValid($values['current_password'])) {
                $form->getComponent('current_password')->addError('Incorrect password');
                $this->redrawControl('form');
                return;
            } else {
                $this->privilegeManager->elevatePrivileges();
            }
        }

        $this->accountManager->updatePassword($account, $values['new_password']);

        if (count($this->onPasswordChanged)) {
            $this->onPasswordChanged();
        } else {
            $this->flashMessage('Your password has been changed successfully.');
            $this->postGet('this');
            $this->redrawControl('form');
        }
    }

    public function createComponentForm() : PasswordForm {
        $form = new PasswordForm(!$this->privilegeManager->arePrivilegesElevated());
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doChangePassword']);
        $form->onError[] = $this->getFormErrorHandler();
        return $form;
    }

}
