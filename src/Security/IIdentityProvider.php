<?php

declare(strict_types=1);

namespace App\Security;

use Nette\Security\IIdentity;


interface IIdentityProvider {

    public function findByCredentials(array $credentials) : ?IIdentity;

}
