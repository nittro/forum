<?php

declare(strict_types=1);

namespace App\PublicModule\Components\NotificationsControl;

use App\ORM\Manager\NotificationManager;
use App\UI\BaseControl;


class NotificationsControl extends BaseControl {

    private $notificationManager;


    public function __construct(NotificationManager $notificationManager) {
        parent::__construct();
        $this->notificationManager = $notificationManager;
    }


}
