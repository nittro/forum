<?php

declare(strict_types=1);

namespace App\PublicModule\Components\RegistrationControl;


interface IRegistrationControlFactory {

    public function create() : RegistrationControl;

}
