<?php

declare(strict_types=1);

namespace App\PublicModule\Components\RegistrationControl;

use App\Entity\CategorySubscription;
use App\ORM\Manager\AccountManager;
use App\ORM\Manager\CategoryManager;
use App\ORM\Manager\CategorySubscriptionManager;
use App\Security\Identity;
use App\Security\PrivilegeElevationManager;
use App\UI\BaseControl;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Kdyby\Doctrine\EntityManager;
use Nette\Security\User;


class RegistrationControl extends BaseControl {

    /** @var callable[] */
    public $onRegistered = [];


    private $em;

    private $accountManager;

    private $categoryManager;

    private $subscriptionManager;

    private $privilegeManager;

    private $user;


    public function __construct(
        EntityManager $em,
        AccountManager $accountManager,
        CategoryManager $categoryManager,
        CategorySubscriptionManager $subscriptionManager,
        PrivilegeElevationManager $privilegeManager,
        User $user
    ) {
        parent::__construct();
        $this->em = $em;
        $this->accountManager = $accountManager;
        $this->categoryManager = $categoryManager;
        $this->subscriptionManager = $subscriptionManager;
        $this->privilegeManager = $privilegeManager;
        $this->user = $user;
    }



    public function render() : void {
        $this->template->autoSubscribed = $this->categoryManager->getAutoSubscribedCategories();
        parent::render();
    }


    private function doRegister(RegistrationForm $form, array $values) : void {
        $this->em->beginTransaction();

        try {
            $account = $this->accountManager->createAccount(
                $values['login'],
                $values['password'],
                $values['name'],
                $values['email'],
                'user',
                !empty($values['subscribe_mentions'])
            );

            if (!empty($values['avatar']) && $values['avatar']->isOk() && $values['avatar']->isImage()) {
                $this->accountManager->updateAvatar($account->getUser(), $values['avatar']->toImage());
            }

            $account->loggedIn();
            $this->user->login(new Identity($account));
            $this->privilegeManager->elevatePrivileges();

            if (!empty($values['subscribe_auto_categories'])) {
                $categories = $this->categoryManager->getAutoSubscribedCategories();

                foreach ($categories as $category) {
                    $this->subscriptionManager->subscribe($category, CategorySubscription::NOTIFICATIONS_DAILY);
                }
            }

            $this->em->commit();
        } catch (UniqueConstraintViolationException $e) {
            $this->em->rollback();
            $this->redrawControl('form');

            if (strpos($e->getMessage(), '(login)') !== false) {
                $form->getComponent('login')->addError('Sorry, this login is already taken!');
            } else {
                $form->getComponent('email')->addError('An account with this e-mail already exists. Did you forget your password?');
            }

            return;
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }

        $this->onRegistered($account);
    }

    private function doHandleFormError(RegistrationForm $form) : void {
        $this->redrawControl('form');
    }

    public function createComponentForm() : RegistrationForm {
        $form = new RegistrationForm();
        $form->onSuccess[] = \Closure::fromCallable([$this, 'doRegister']);
        $form->onError[] = \Closure::fromCallable([$this, 'doHandleFormError']);
        return $form;
    }

}
