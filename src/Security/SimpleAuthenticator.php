<?php

declare(strict_types=1);

namespace App\Security;

use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;


class SimpleAuthenticator implements IAuthenticator {

    private $identityProvider;


    public function __construct(IIdentityProvider $identityProvider) {
        $this->identityProvider = $identityProvider;
    }

    /**
     * @param array $credentials
     * @return IIdentity
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials) : IIdentity {
        $identity = $this->identityProvider->findByCredentials($credentials);

        if (!$identity) {
            throw new AuthenticationException();
        }

        return $identity;
    }

}
