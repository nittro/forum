<?php

declare(strict_types=1);

namespace App\PublicModule\Components\UserControl;


interface IUserControlFactory {

    public function create() : UserControl;

}
