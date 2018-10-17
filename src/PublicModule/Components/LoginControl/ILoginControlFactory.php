<?php

declare(strict_types=1);

namespace App\PublicModule\Components\LoginControl;


interface ILoginControlFactory {

    public function create() : LoginControl;

}
