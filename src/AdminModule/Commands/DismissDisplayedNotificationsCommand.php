<?php

declare(strict_types=1);

namespace App\AdminModule\Commands;

use App\ORM\Manager\NotificationManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class DismissDisplayedNotificationsCommand extends Command {

    private $notificationManager;


    public function __construct(NotificationManager $notificationManager) {
        parent::__construct();
        $this->notificationManager = $notificationManager;
    }


    protected function configure() : void {
        $this->setName('notifications:dismiss-displayed');
    }


    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $this->notificationManager->dismissOldDisplayedNotifications();
        return 0;
    }

}
