<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Topic;
use App\Entity\User;
use App\ORM\Lookup\UserLookup;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\ResultSetMapping;
use Nette\Security\User as AppUser;
use Kdyby\Doctrine\EntityManager;


class UserManager {

    private $entityManager;

    private $currentUser;

    private $currentUserEntity;

    private $users;


    public function __construct(EntityManager $entityManager, AppUser $user) {
        $this->entityManager = $entityManager;
        $this->currentUser = $user;
        $this->users = $entityManager->getRepository(User::class);
    }


    public function lookup() : UserLookup {
        return new UserLookup([$this->users, 'createQueryBuilder']);
    }


    public function isUserLoggedIn() : bool {
        return $this->currentUser->isLoggedIn();
    }

    public function getCurrentUser() : ?User {
        if (!isset($this->currentUserEntity)) {
            if (!$this->currentUser->isLoggedIn()) {
                return null;
            }

            $this->currentUserEntity = $this->users->find($this->currentUser->getId());
        }

        return $this->currentUserEntity;
    }

    public function getCurrentUserId() : ?int {
        return $this->currentUser->isLoggedIn() ? $this->currentUser->getId() : null;
    }


    public function getByIDs(array $ids) : array {
        $qb = $this->users->createQueryBuilder('u');

        $qb->whereCriteria([
            'u.id' => $ids,
        ]);

        return $qb->getQuery()->getResult();
    }


    /**
     * @param array $logins
     * @return User[]
     */
    public function getByLogins(array $logins) : array {
        $builder = $this->users->createQueryBuilder('u');
        $builder->select('u, partial a.{id, subscribeMentions}');
        $builder->innerJoin('u.account', 'a');
        $builder->whereCriteria(['u.login' => $logins]);
        return array_column($builder->getQuery()->getResult(), null, 'login');
    }


    public function getById(int $id, bool $need = true) : ?User {
        if ($user = $this->users->find($id)) {
            return $user;
        } else if ($need) {
            throw new NoResultException();
        } else {
            return null;
        }
    }

    public function getByLogin(string $login, bool $need = true) : ?User {
        if ($user = $this->users->findOneBy(['login' => $login])) {
            return $user;
        } else if ($need) {
            throw new NoResultException();
        } else {
            return null;
        }
    }


    public function suggestByLogin(?string $login, ?Topic $topic = null, int $limit = 10) : array {
        $mapping = new ResultSetMapping();
        $mapping->addScalarResult('login', 'login');

        $params = [null];
        $tq = '';
        $lq = '';
        $sort = 'u.login ASC ';

        if ($topic) {
            $tq = 'LEFT JOIN LATERAL (' .
                'SELECT p.id FROM posts p ' .
                'WHERE p.topic_id = ? AND p.author_id = u.id ' .
                'ORDER BY p.id DESC LIMIT 1' .
            ') lp ON TRUE ';

            $sort = 'COALESCE(lp.id, 0) DESC ';
            $params[] = $topic->getId();
        }

        if ($login) {
            $lq = 'WHERE u.login ILIKE ? ';
            $params[] = $login . '%';
        }

        $query = $this->users->createNativeQuery(
            'SELECT u.login FROM users u ' .
            $tq . $lq .
            'ORDER BY ' . $sort .
            'LIMIT ?',
            $mapping
        );

        $params[] = $limit;
        unset($params[0]);
        $query->setParameters($params);

        return array_column($query->getArrayResult(), 'login');
    }

}
