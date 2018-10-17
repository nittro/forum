<?php

declare(strict_types=1);

namespace App\AdminModule\Commands;

use App\Console\Helpers;
use App\Console\InteractionHelper;
use App\Entity\Account;
use Kdyby\Doctrine\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ChangePasswordCommand extends Command {

    private $entityManager;

    private $userManager;


    public function __construct(EntityManager $entityManager) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userManager = $entityManager->getRepository(Account::class);
    }


    protected function configure() : void {
        $this
            ->setName('user:passwd')
            ->setDescription('Change a user\'s password')
            ->addArgument('login', InputArgument::REQUIRED, 'The login of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'The new password')
        ;
    }


    protected function interact(InputInterface $input, OutputInterface $output) : void {
        /** @var QuestionHelper $helper */
        $questionHelper = $this->getHelper('question');
        $interaction = new InteractionHelper($input, $output, $questionHelper);

        $interaction->ensureArgumentIsValid('login', \Closure::fromCallable([$this, 'validateLogin']), 'Please specify the login of the user whose password you want to change:');
        $interaction->ensureArgumentIsValid('password', Helpers::getPasswordValidator(), 'Please specify the new password:', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        $user = $this->userManager->findOneBy(['user.login' => $input->getArgument('login')]);

        if (!$user) {
            $output->writeln('<error>Sorry, no user with the specified login was found!</error>');
            return 1;
        } else {
            $user->setPassword($input->getArgument('password'));
            $this->entityManager->persist($user);
            $this->entityManager->flush();
            $output->writeln('<info>Password changed successfully.</info>');
            return 0;
        }
    }


    private function validateLogin(?string $login, bool $need = true) : void {
        if (empty($login)) {
            if ($need) {
                throw new \RuntimeException('Please specify a login');
            }
        } else if (!$this->userManager->findOneBy(['user.login' => $login])) {
            throw new \RuntimeException('No user with the specified login exists');
        }
    }

}
