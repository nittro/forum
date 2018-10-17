<?php

declare(strict_types=1);

namespace App\PublicModule\Components\NotificationsControl;


interface INotificationsControlFactory {

    public function create() : NotificationsControl;

}
