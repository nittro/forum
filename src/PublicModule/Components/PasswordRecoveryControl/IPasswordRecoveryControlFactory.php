<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PasswordRecoveryControl;


interface IPasswordRecoveryControlFactory {

    public function create() : PasswordRecoveryControl;

}
