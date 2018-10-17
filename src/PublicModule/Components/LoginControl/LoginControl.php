<?php

declare(strict_types=1);

namespace App\PublicModule\Components\LoginControl;

use App\Security\PrivilegeElevationManager;
use App\UI\BaseControl;
use Nette\Application\UI\Form;
use Nette\Security\AuthenticationException;
use Nette\Security\User;


class LoginControl extends BaseControl {

    /** @var callable[] */
    public $onLogin = [];

    /** @var User */
    private $user;

    private $privilegeManager;


    public function __construct(User $user, PrivilegeElevationManager $privilegeManager) {
        parent::__construct();
        $this->user = $user;
        $this->privilegeManager = $privilegeManager;
    }

    private function doLogin(Form $form, array $credentials) : void {
        try {
            $this->user->login($credentials['login'], $credentials['password']);
            $this->privilegeManager->elevatePrivileges();
            $this->onLogin();

        } catch (AuthenticationException $e) {
            $form->addError('Invalid credentials');
            $this->redrawControl('form');
        }
    }

    public function createComponentForm() : Form {
        $form = new Form();

        $form->addText('login', 'Login:')->setRequired();
        $form->addPassword('password', 'Password:')->setRequired();
        $form->addSubmit('exec', 'Log in');

        $form->addProtection();
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doLogin']);

        return $form;
    }

}
