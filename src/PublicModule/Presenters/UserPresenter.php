<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use App\Entity\Account;
use App\ORM\Manager\PasswordRequestManager;
use App\PublicModule\Components\LoginControl\ILoginControlFactory;
use App\PublicModule\Components\LoginControl\LoginControl;
use App\PublicModule\Components\PasswordControl\IPasswordControlFactory;
use App\PublicModule\Components\PasswordControl\PasswordControl;
use App\PublicModule\Components\PasswordRecoveryControl\IPasswordRecoveryControlFactory;
use App\PublicModule\Components\PasswordRecoveryControl\PasswordRecoveryControl;
use App\PublicModule\Components\RegistrationControl\IRegistrationControlFactory;
use App\PublicModule\Components\RegistrationControl\RegistrationControl;
use App\PublicModule\Components\SettingsControl\ISettingsControlFactory;
use App\PublicModule\Components\SettingsControl\SettingsControl;
use App\Security\Identity;
use App\Security\PrivilegeElevationManager;
use Nette\Security\AuthenticationException;


class UserPresenter extends BasePresenter {

    private $loginControlFactory;

    private $registrationControlFactory;

    private $settingsControlFactory;

    private $passwordControlFactory;

    private $passwordRecoveryControlFactory;

    private $passwordRequestManager;

    private $privilegeManager;


    public function __construct(
        ILoginControlFactory $loginControlFactory,
        IRegistrationControlFactory $registrationControlFactory,
        ISettingsControlFactory $settingsControlFactory,
        IPasswordControlFactory $passwordControlFactory,
        IPasswordRecoveryControlFactory $passwordRecoveryControlFactory,
        PasswordRequestManager $passwordRequestManager,
        PrivilegeElevationManager $privilegeManager
    ) {
        parent::__construct();
        $this->loginControlFactory = $loginControlFactory;
        $this->registrationControlFactory = $registrationControlFactory;
        $this->settingsControlFactory = $settingsControlFactory;
        $this->passwordControlFactory = $passwordControlFactory;
        $this->passwordRecoveryControlFactory = $passwordRecoveryControlFactory;
        $this->passwordRequestManager = $passwordRequestManager;
        $this->privilegeManager = $privilegeManager;
    }

    public function actionLogout(?string $redir = null) : void {
        $this->getUser()->logout(true);
        $this->getSession()->destroy();
        $this->payload->redraw = 'full';

        if ($redir) {
            $this->redirectUrl($redir);
        } else if ($this->isAjax()) {
            $this->forward('Home:');
        } else {
            $this->redirect('Home:');
        }
    }

    public function actionRegistration() : void {
        if ($this->getUser()->isLoggedIn()) {
            $this->payload->redraw = 'full';
            $this->redirect('Home:');
        }
    }

    public function actionSettings() : void {
        if (!$this->getUser()->isLoggedIn()) {
            $this->payload->redraw = 'full';
            $this->redirect('login');
        }
    }


    public function actionResetPassword(?int $rid = null, ?string $token = null) : void {
        if ($rid && $token) try {
            $request = $this->passwordRequestManager->validateRequest($rid, $token);
            $this->getUser()->login(new Identity($request->getAccount()));
            $this->privilegeManager->elevatePrivileges();
        } catch (AuthenticationException $e) {
            return;
        }

        if (isset($request) && $this->getUser()->isLoggedIn()) {
            $this->getComponent('password')->onPasswordChanged[] = function () use ($request) {
                $this->passwordRequestManager->removeRequest($request);

                $this->flashMessage('Your password has been changed successfully.');
                $this->payload->redraw = 'full';
                $this->payload->scrollTo = 0;
                $this->redirect('Home:');
            };
        }
    }


    public function renderLogin(?string $redir = null, ?string $backlink = null) : void {
        if ($this->getUser()->isLoggedIn()) {
            $this->payload->redraw = 'full';

            if ($redir) {
                if (preg_match('~^/admin(/|$)~', $redir)) {
                    $this->disallowAjax();
                }

                $this->redirectUrl($redir);
            } else if ($backlink) {
                $this->restoreRequest($backlink);
            } else if ($this->isAjax()) {
                $this->forward('Home:');
            } else {
                $this->redirect('Home:');
            }
        }
    }

    public function createComponentLogin() : LoginControl {
        return $this->loginControlFactory->create();
    }

    public function createComponentRegistration() : RegistrationControl {
        $control = $this->registrationControlFactory->create();

        $control->onRegistered[] = function(Account $account) {
            $this->flashMessage(sprintf('Welcome, @%s!', $account->getUser()->getLogin()));
            $this->payload->redraw = 'full';
            $this->redirect('Home:');
        };

        return $control;
    }

    public function createComponentSettings() : SettingsControl {
        $this->denyUnlessAuthorized();
        return $this->settingsControlFactory->create();
    }

    public function createComponentPassword() : PasswordControl {
        $this->denyUnlessAuthorized();
        return $this->passwordControlFactory->create();
    }

    public function createComponentRecovery() : PasswordRecoveryControl {
        return $this->passwordRecoveryControlFactory->create();
    }

}
