<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Account;
use App\ORM\Manager\AccountManager;
use Nette\Security\IIdentity;


class DoctrineIdentityProvider implements IIdentityProvider {

    private $accountManager;

    public function __construct(AccountManager $accountManager) {
        $this->accountManager = $accountManager;
    }


    public function findByCredentials(array $credentials) : ?IIdentity {
        if (empty($credentials)) {
            return null;
        }

        [$login, $password] = $credentials;

        /** @var Account $account */
        $account = $this->accountManager->getByLogin($login);

        if ($account && $account->isPasswordValid($password)) {
            $identity = new Identity($account);
            $this->accountManager->updateLastLogin($account);
            return $identity;
        } else {
            return null;
        }
    }


}
