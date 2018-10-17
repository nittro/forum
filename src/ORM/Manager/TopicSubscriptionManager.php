<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\TopicSubscription;
use Kdyby\Doctrine\Dql\Join;
use Kdyby\Doctrine\EntityManager;


class TopicSubscriptionManager {

    private $em;

    private $userManager;

    private $subscriptions;


    /** @var TopicSubscription[] */
    private $cache = [];


    public function __construct(EntityManager $em, UserManager $userManager) {
        $this->em = $em;
        $this->userManager = $userManager;
        $this->subscriptions = $em->getRepository(TopicSubscription::class);
    }


    public function getSubscription(Topic $topic, bool $create = true) : ?TopicSubscription {
        if (!isset($this->cache[$topic->getId()]) && ($create || !key_exists($topic->getId(), $this->cache))) {
            $subscription = $this->subscriptions->findOneBy([
                'topic' => $topic,
                'user' => $this->userManager->getCurrentUserId(),
            ]);

            if (!$subscription && $create) {
                $subscription = new TopicSubscription($this->userManager->getCurrentUser(), $topic);
            }

            $this->cache[$topic->getId()] = $subscription;
        }

        return $this->cache[$topic->getId()];
    }

    public function subscribe(Topic $topic) : void {
        $subscription = $this->getSubscription($topic, true);
        $subscription->setNotificationLevel(TopicSubscription::NOTIFICATIONS_INSTANT);
        $this->em->persist($subscription);
        $this->em->flush();
    }

    public function unsubscribe(Topic $topic) : void {
        if ($subscription = $this->getSubscription($topic, false)) {
            $subscription->setNotificationLevel(TopicSubscription::NOTIFICATIONS_OFF);
            $this->em->persist($subscription);
            $this->em->flush();
        }
    }

    public function markAsRead(Post $post) : void {
        if ($this->userManager->isUserLoggedIn()) {
            $subscription = $this->getSubscription($post->getTopic());
            $subscription->markAsRead($post);
            $this->em->persist($subscription);
            $this->em->flush();
        }
    }

    public function getUnreadRepliesPerTopic(array $ids) : array {
        $builder = $this->subscriptions->createQueryBuilder('s');
        $builder->select('IDENTITY(s.topic) AS topic, COUNT(p.id) AS unread');
        $builder->leftJoin(Post::class, 'p', Join::WITH, 'p.topic = s.topic AND p.id > s.lastRead');
        $builder->whereCriteria([
            's.user' => $this->userManager->getCurrentUserId(),
            's.topic' => $ids,
        ]);
        $builder->groupBy('s.topic');
        return array_column($builder->getQuery()->getArrayResult(), 'unread', 'topic');
    }

    public function getFirstUnreadPost(Topic $topic) : ?int {
        $builder = $this->subscriptions->createQueryBuilder('s');
        $builder->select('p.id');
        $builder->leftJoin(Post::class, 'p', Join::WITH, 'p.topic = s.topic AND p.id > s.lastRead');
        $builder->whereCriteria(['s.topic' => $topic]);
        $builder->orderBy('p.id', 'ASC');
        $builder->setMaxResults(1);
        return $builder->getQuery()->getSingleScalarResult();
    }

    public function subscribeMentionedUsers(Topic $topic, array $users) : void {
        $this->em->beginTransaction();
        $this->cache = [];

        try {
            $existing = $this->subscriptions->createQueryBuilder('s');
            $existing->select('IDENTITY(s.user) AS uid');
            $existing->whereCriteria([
                's.topic' => $topic,
                's.user' => $users,
            ]);
            $existing = array_column($existing->getQuery()->getArrayResult(), 'uid', 'uid');

            $update = $this->subscriptions->createQueryBuilder();
            $update->update(TopicSubscription::class, 's');
            $update->set('s.notificationLevel', TopicSubscription::NOTIFICATIONS_INSTANT);
            $update->whereCriteria(['s.user' => $existing]);
            $update->getQuery()->execute();

            $users = array_diff_key(array_column($users, null, 'id'), $existing);
            $i = 0;

            foreach ($users as $user) {
                $subscription = new TopicSubscription($user, $topic, TopicSubscription::NOTIFICATIONS_INSTANT);
                $this->em->persist($subscription);

                if (($i % 20) === 0) {
                    $this->em->flush();
                    $this->em->clear(TopicSubscription::class);
                }
            }

            $this->em->flush();
            $this->em->clear(TopicSubscription::class);
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

}
