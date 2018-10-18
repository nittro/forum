<?php

declare(strict_types=1);

namespace App\ORM\Lookup;

use App\Entity\Category;
use App\Entity\Post;
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

    /** @var array */
    private $replyCounts;

    /** @var array */
    private $unreadCounts;

    /** @var int */
    private $unreadBy = null;


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
        $this->unreadBy = $uid;

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

    public function withUnreadCounts(int $uid) : self {
        $this->unreadBy = $uid;
        return $this;
    }


    public function getReplyCount(Topic $topic) : int {
        if (!isset($this->replyCounts)) {
            $ids = $this->extract('id');

            $builder = $this->createQueryBuilder()->resetDQLPart('from');
            $builder->select('IDENTITY(p.topic) tid, COUNT(p.id) - 1 c');
            $builder->from(Post::class, 'p');
            $builder->whereCriteria(['p.topic' => $ids]);
            $builder->groupBy('p.topic');
            $this->replyCounts = array_column($builder->getQuery()->getArrayResult(), 'c', 'tid');
        }

        return $this->replyCounts[$topic->getId()] ?? 0;
    }


    public function getUnreadReplies(Topic $topic) : int {
        if (!isset($this->unreadBy)) {
            throw new \RuntimeException("Call unreadBy() or withUnreadCounts() to load unread reply counts");
        } else if (!isset($this->unreadCounts)) {
            $ids = $this->extract('id');

            $builder = $this->createQueryBuilder()->resetDQLPart('from');
            $builder->select('IDENTITY(s.topic) AS topic, COUNT(p.id) AS unread');
            $builder->from(TopicSubscription::class, 's');
            $builder->leftJoin(Post::class, 'p', Join::WITH, 'p.topic = s.topic AND p.id > s.lastRead');
            $builder->whereCriteria([
                's.user' => $this->unreadBy,
                's.topic' => $ids,
            ]);
            $builder->groupBy('s.topic');
            $this->unreadCounts = array_column($builder->getQuery()->getArrayResult(), 'unread', 'topic');
        }

        return $this->unreadCounts[$topic->getId()] ?? 0;
    }


    protected function createSelectQueryBuilder() : QueryBuilder {
        $builder = parent::createSelectQueryBuilder();
        $builder->indexBy('t', 't.id');
        $builder->innerJoin('t.author', 'a');
        $builder->orderBy('t.lastPost', 'DESC');
        return $builder;
    }

}
