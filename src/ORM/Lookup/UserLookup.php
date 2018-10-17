<?php

declare(strict_types=1);

namespace App\ORM\Lookup;

use App\Entity\Post;
use App\Entity\User;
use Kdyby\Doctrine\QueryBuilder;


/**
 * @method \Generator|User[] getIterator()
 * @method User[] toArray()
 */
class UserLookup extends AbstractLookup {

    /** @var array */
    private $postCounts;


    public function __construct(callable $queryBuilderFactory) {
        parent::__construct($queryBuilderFactory, 'u');
    }


    public function withAccounts() : self {
        $this->addSelectModifier(function (QueryBuilder $builder) : void {
            $builder->addSelect('a')->innerJoin('u.account', 'a');
        });

        return $this;
    }


    public function newestFirst() : self {
        $this->addSelectModifier(function (QueryBuilder $builder) : void {
            $builder->orderBy('u.id', 'DESC');
        });

        return $this;
    }

    public function sortByName() : self {
        $this->addSelectModifier(function (QueryBuilder $builder) : void {
            $builder->orderBy('u.name', 'ASC');
        });

        return $this;
    }

    public function getPostCount(User $user) : int {
        if (!isset($this->postCounts)) {
            $ids = $this->extract('id');

            $builder = $this->createQueryBuilder()->resetDQLPart('from');
            $builder->select('IDENTITY(p.author) uid, COUNT(p.id) c');
            $builder->from(Post::class, 'p');
            $builder->whereCriteria(['p.author' => $ids]);
            $builder->groupBy('p.author');
            $this->postCounts = array_column($builder->getQuery()->getArrayResult(), 'c', 'uid');
        }

        return $this->postCounts[$user->getId()] ?? 0;
    }

}
