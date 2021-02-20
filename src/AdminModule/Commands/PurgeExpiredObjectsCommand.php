<?php

declare(strict_types=1);

namespace App\AdminModule\Commands;

use App\ORM\Manager\PasswordRequestManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class PurgeExpiredObjectsCommand extends Command
{
    private $passwordRequestManager;

    public function __construct(PasswordRequestManager $passwordRequestManager)
    {
        parent::__construct();
        $this->passwordRequestManager = $passwordRequestManager;
    }

    protected function configure() : void
    {
        $this->setName('cron:purge-expired-objects')
            ->setDescription('Purges expired objects from the database');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->passwordRequestManager->purgeExpiredRequests();
        return 0;
    }
}
