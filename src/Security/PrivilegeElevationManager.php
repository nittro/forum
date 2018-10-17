<?php

declare(strict_types=1);

namespace App\Security;

use Nette\Http\SessionSection;


class PrivilegeElevationManager {

    private $session;


    public function __construct(SessionSection $session) {
        $this->session = $session;
    }


    public function elevatePrivileges() : void {
        $this->session['elevated'] = true;
        $this->session->setExpiration(new \DateTimeImmutable('+10 minutes'));
    }


    public function arePrivilegesElevated() : bool {
        return !empty($this->session['elevated']);
    }

}
