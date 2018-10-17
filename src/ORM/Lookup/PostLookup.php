<?php

declare(strict_types=1);

namespace App\ORM\Lookup;

use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use Kdyby\Doctrine\QueryBuilder;


/**
 * @method \Generator|Post[] getIterator()
 * @method Post[] toArray()
 */
class PostLookup extends AbstractLookup {

    public function __construct(callable $queryBuilderFactory) {
        parent::__construct($queryBuilderFactory, 'p');
    }


    public function inTopic(Topic $topic) : self {
        $this->addCommonModifier(function (QueryBuilder $builder) use ($topic) : void {
            $builder->andWhere('p.topic = :topic')
                ->setParameter('topic', $topic);
        });

        $this->addSelectModifier(function (QueryBuilder $builder) : void {
            $builder->innerJoin('p.author', 'a')
                ->addSelect('a');
        });

        return $this;
    }


    public function byUser(User $user) : self {
        $this->addCommonModifier(function (QueryBuilder $builder) use ($user) : void {
            $builder->andWhere('p.author = :author')
                ->setParameter('author', $user);
        });

        $this->addSelectModifier(function (QueryBuilder $builder) : void {
            $builder->innerJoin('p.topic', 't')
                ->addSelect('t');
        });

        return $this;
    }

    protected function createSelectQueryBuilder() : QueryBuilder {
        $builder = parent::createSelectQueryBuilder();
        $builder->orderBy('p.id', 'ASC');
        return $builder;
    }

}
