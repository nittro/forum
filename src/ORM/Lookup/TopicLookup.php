<?php

declare(strict_types=1);

namespace App\ORM\Lookup;

use App\Entity\Category;
use App\Entity\Topic;
use App\Entity\TopicSubscription;
use App\Entity\User;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\QueryBuilder;


/**
 * @method \Generator|Topic[] getIterator()
 * @method Topic[] toArray()
 */
class TopicLookup extends AbstractLookup {

    public function __construct(callable $queryBuilderFactory) {
        parent::__construct($queryBuilderFactory, 't');
    }


    public function inCategory(Category $category) : self {
        $this->addCommonModifier(function(QueryBuilder $builder) use ($category) : void {
            $builder->andWhere('t.category = :category')
                ->setParameter('category', $category);
        });

        return $this;
    }

    public function byUser(User $user) : self {
        $this->addCommonModifier(function(QueryBuilder $builder) use ($user) : void {
            $builder->andWhere('t.author = :author')
                ->setParameter('author', $user);
        });

        return $this;
    }

    public function unreadBy(int $uid) : self {
        $this->addCommonModifier(function(QueryBuilder $builder) use ($uid) : void {
            $builder->innerJoin(TopicSubscription::class, 's', Join::WITH, 's.topic = t AND s.user = :uid AND (s.lastRead IS NULL OR s.lastRead < t.lastPost)')
                ->setParameter('uid', $uid);
        });

        return $this;
    }

    public function readOrUnsubscribedBy(int $uid) : self {
        $this->addCommonModifier(function(QueryBuilder $builder) use ($uid) : void {
            $builder->leftJoin(TopicSubscription::class, 's', Join::WITH, 's.topic = t AND s.user = :uid')
                ->andWhere('s.id IS NULL OR s.lastRead >= t.lastPost')
                ->setParameter('uid', $uid);
        });

        return $this;
    }


    public function withCategory() : self {
        $this->addSelectModifier(function(QueryBuilder $builder) : void {
            $builder->addSelect('c')->innerJoin('t.category', 'c');
        });

        return $this;
    }

    public function withLastPost() : self {
        $this->addSelectModifier(function (QueryBuilder $builder) : void {
            $builder->addSelect('lp, lpa');
            $builder->innerJoin('t.lastPost', 'lp');
            $builder->innerJoin('lp.author', 'lpa');
        });

        return $this;
    }


    protected function createSelectQueryBuilder() : QueryBuilder {
        $builder = parent::createSelectQueryBuilder();
        $builder->indexBy('t', 't.id');
        $builder->innerJoin('t.author', 'a');
        $builder->orderBy('t.lastPost', 'DESC');
        return $builder;
    }

}
