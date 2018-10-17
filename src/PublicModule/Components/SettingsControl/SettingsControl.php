<?php

declare(strict_types=1);

namespace App\PublicModule\Components\SettingsControl;

use App\ORM\Manager\AccountManager;
use App\Security\Identity;
use App\Security\PrivilegeElevationManager;
use App\UI\BaseControl;
use App\UI\FormErrorHandlerTrait;
use Nette\Security\User;


class SettingsControl extends BaseControl {
    use FormErrorHandlerTrait;

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
        $this->template->requirePassword = !$this->privilegeManager->arePrivilegesElevated();
        parent::render();
    }


    private function doSave(SettingsForm $form, array $values) : void {
        $account = $this->accountManager->getById($this->user->getId());

        if (!$this->privilegeManager->arePrivilegesElevated()) {
            if (!$account->isPasswordValid($values['password'])) {
                $form->getComponent('password')->addError('Incorrect password');
                $this->redrawControl('form');
                return;
            } else {
                $this->privilegeManager->elevatePrivileges();
            }
        }

        $this->accountManager->updateSettings(
            $account,
            $values['name'],
            $values['email'],
            !empty($values['subscribe_mentions'])
        );

        if (!empty($values['avatar']) && $values['avatar']->isOk() && $values['avatar']->isImage()) {
            $this->accountManager->updateAvatar($account->getUser(), $values['avatar']->toImage());
        }

        $this->user->login(new Identity($account));

        $this->flashMessage('Changes have been saved.');
        $this->postGet('this');
        $this->redrawControl('form');
    }


    public function createComponentForm() : SettingsForm {
        $form = new SettingsForm(!$this->privilegeManager->arePrivilegesElevated());
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doSave']);
        $form->onError[] = $this->getFormErrorHandler();

        /** @var Identity $identity */
        $identity = $this->user->getIdentity();

        $form->setDefaults([
            'name' => $identity->getName(),
            'email' => $identity->getEmail(),
            'subscribe_mentions' => $identity->isSubscribeMentions(),
        ]);

        return $form;
    }

}
