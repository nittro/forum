<?php

declare(strict_types=1);

namespace App\PublicModule\Components\SettingsControl;


interface ISettingsControlFactory {

    public function create() : SettingsControl;

}
