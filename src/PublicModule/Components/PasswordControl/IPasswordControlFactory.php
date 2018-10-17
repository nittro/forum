<?php

declare(strict_types=1);

namespace App\PublicModule\Components\PasswordControl;


interface IPasswordControlFactory {

    public function create() : PasswordControl;

}
