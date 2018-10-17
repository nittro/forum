<?php

declare(strict_types=1);

namespace App\AdminModule\Commands;

use App\Console\Helpers;
use App\Console\InteractionHelper;
use App\Entity\Account;
use App\Entity\User;
use App\ORM\Manager\AccountManager;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateUserCommand extends Command {

    private $accountManager;


    public function __construct(AccountManager $accountManager) {
        parent::__construct();
        $this->accountManager = $accountManager;
    }


    protected function configure() : void {
        $this
            ->setName('user:create')
            ->setDescription('Create a new user account')
            ->addArgument('login', InputArgument::REQUIRED, 'The login of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the user')
            ->addArgument('email', InputArgument::REQUIRED, 'The e-mail of the user')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the user')
            ->addArgument('roles', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'The roles to grant the user', ['admin'])
        ;
    }


    protected function interact(InputInterface $input, OutputInterface $output) : void {
        /** @var QuestionHelper $helper */
        $questionHelper = $this->getHelper('question');
        $interaction = new InteractionHelper($input, $output, $questionHelper);

        $interaction->ensureArgumentIsValid('login', \Closure::fromCallable([$this, 'validateLogin']), 'Please specify the login of the new user:');
        $interaction->ensureArgumentIsValid('password', Helpers::getPasswordValidator(), 'Please specify a password for the new user:', true);
        $interaction->ensureArgumentIsValid('email', \Closure::fromCallable([$this, 'validateEmail']), 'Please specify the e-mail of the new user:');
        $interaction->ensureArgumentIsValid('name', \Closure::fromCallable([$this, 'validateName']), 'Please specify the name of the new user:');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int {
        try {
            $this->accountManager->createAccount(
                $input->getArgument('login'),
                $input->getArgument('password'),
                $input->getArgument('name'),
                $input->getArgument('email'),
                $input->getArgument('roles')
            );

            $output->writeln('<info>User created.</info>');
            return 0;

        } catch (UniqueConstraintViolationException $e) {
            $output->writeln('<error>Sorry, the login or e-mail address you provided is already taken!</error>');
            return 1;
        }
    }

    private function validateLogin(?string $login, bool $need = true) : void {
        if (empty($login)) {
            if ($need) {
                throw new \RuntimeException('Login cannot be empty');
            }
        } else if ($this->accountManager->isLoginRegistered($login)) {
            throw new \RuntimeException('That login already exists');
        }
    }

    private function validateName(?string $name, bool $need = true) : void {
        if (empty($name)) {
            if ($need) {
                throw new \RuntimeException('Name cannot be empty');
            }
        }
    }

    private function validateEmail(?string $email, bool $need = true) : void {
        if (empty($email)) {
            if ($need) {
                throw new \RuntimeException('E-mail cannot be empty');
            }
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Please specify a valid e-mail address');
        } else if ($this->accountManager->isEmailRegistered($email)) {
            throw new \RuntimeException('That e-mail is already taken');
        }
    }

}
