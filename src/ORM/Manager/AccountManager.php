<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Account;
use App\Entity\User;
use Doctrine\ORM\NoResultException;
use Kdyby\Doctrine\EntityManager;
use Nette\Utils\Image;


class AccountManager {

    private $em;

    private $avatarManager;

    private $accounts;


    public function __construct(EntityManager $em, AvatarManager $avatarManager) {
        $this->em = $em;
        $this->avatarManager = $avatarManager;
        $this->accounts = $em->getRepository(Account::class);
    }

    public function getById(int $id, bool $need = true) : ?Account {
        if ($account = $this->accounts->find($id)) {
            return $account;
        } else if ($need) {
            throw new NoResultException();
        } else {
            return null;
        }
    }

    public function getByLogin(string $login) : ?Account {
        $builder = $this->accounts->createQueryBuilder('a');
        $builder->select('a, u');
        $builder->innerJoin('a.user', 'u');
        $builder->where('u.login = :login');
        $builder->setParameter('login', $login);
        return $builder->getQuery()->getOneOrNullResult();
    }

    public function getByLoginOrEmail(string $identifier) : ?Account {
        $builder = $this->accounts->createQueryBuilder('a');
        $builder->select('a, u');
        $builder->innerJoin('a.user', 'u');
        $builder->where('u.login = :identifier OR u.email = :identifier');
        $builder->setParameter('identifier', $identifier);
        return $builder->getQuery()->getOneOrNullResult();
    }


    public function isLoginRegistered(string $login) : bool {
        return $this->isRegistered('login', $login);
    }

    public function isEmailRegistered(string $email) : bool {
        return $this->isRegistered('email', $email);
    }

    private function isRegistered(string $property, string $value) : bool {
        $builder = $this->accounts->createQueryBuilder('a');
        $builder->select('a.id');
        $builder->innerJoin('a.user', 'u');
        $builder->where(sprintf('u.%s = :v', $property));
        $builder->setParameter('v', $value);
        return (bool) $builder->getQuery()->getOneOrNullResult();
    }


    public function updateLastLogin(Account $account) : void {
        $account->loggedIn();
        $this->em->persist($account);
        $this->em->flush();
    }


    public function updateAvatar(User $user, Image $avatar) : void {
        $this->em->beginTransaction();

        try {
            $user->avatarUpdated();
            $this->em->persist($user);
            $this->em->flush();

            $this->avatarManager->saveAvatar($user, $avatar);

            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function updatePassword(Account $account, string $password) : void {
        $account->setPassword($password);
        $this->em->persist($account);
        $this->em->flush();
    }


    public function updateSettings(Account $account, string $name, string $email, bool $subscribeMentions) : void {
        $user = $account->getUser();
        $user->setName($name);
        $user->setEmail($email);
        $this->em->persist($user);

        $account->setSubscribeMentions($subscribeMentions);
        $this->em->persist($account);

        $this->em->flush();
    }


    public function createAccount(
        string $login,
        string $password,
        string $name,
        string $email,
        $roles = 'user',
        bool $subscribeMentions = true
    ) : Account {
        $this->em->beginTransaction();

        try {
            $account = new Account(
                $password,
                $roles,
                $subscribeMentions
            );

            $this->em->persist($account);
            $this->em->flush();

            $user = new User(
                $account,
                $login,
                $name,
                $email
            );

            $this->em->persist($user);
            $this->em->flush();

            $account->injectUser($user);

            $this->em->commit();
            return $account;

        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

}
