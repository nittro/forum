<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Notification;
use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\Query\ResultSetMapping;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Doctrine\Mapping\ResultSetMappingBuilder;


class NotificationManager {

    private $em;

    private $notifications;

    private $userManager;


    public function __construct(EntityManager $em, UserManager $userManager) {
        $this->em = $em;
        $this->notifications = $em->getRepository(Notification::class);
        $this->userManager = $userManager;
    }


    public function createNotifications(Post $post) : void {
        $mapping = new ResultSetMapping();
        $mapping->isSelect = false;

        $sql = 'INSERT INTO "notifications" ("id", "user_id", "post_id", "created_on", "level", "displayed") ' .
            'SELECT nextval(\'notifications_id_seq\'::regclass), "t"."user_id", ?::int, ?::timestamp, "t"."notification_level", FALSE ' .
            'FROM "topic_subscriptions" "t" ' .
            'WHERE "t"."topic_id" = ? AND "t"."user_id" != ? AND "t"."notification_level" != 0';

        $params = [
            1 => $post->getId(),
            2 => $post->getPostedOn(),
            3 => $post->getTopic()->getId(),
            4 => $post->getAuthor()->getId(),
        ];

        if ($post->getId() === $post->getTopic()->getFirstPost()->getId()) {
            $sql .= ' UNION ALL ' .
                'SELECT nextval(\'notifications_id_seq\'::regclass), "c"."user_id", ?::int, ?::timestamp, "c"."notification_level", FALSE ' .
                'FROM "category_subscriptions" "c" ' .
                'WHERE "c"."category_id" = ? AND "c"."user_id" != ? AND "c"."notification_level" != 0';

            $params += [
                5 => $post->getId(),
                6 => $post->getPostedOn(),
                7 => $post->getTopic()->getCategory()->getId(),
                8 => $post->getAuthor()->getId(),
            ];
        }

        $query = $this->notifications->createNativeQuery($sql, $mapping);
        $query->execute($params);
    }


    public function getNotificationsForCurrentUser(?int $since = null) : array {
        $query = $this->notifications->createQuery(
            'UPDATE App:Notification n SET n.displayed = TRUE WHERE n.user = :user AND n.displayed = FALSE' .
            ($since ? ' AND n.id > :since' : '')
        );

        $query->execute(array_filter([
            'user' => $this->userManager->getCurrentUserId(),
            'since' => $since,
        ]));

        $builder = $this->notifications->createQueryBuilder('n');
        $builder->select('PARTIAL n.{id}, PARTIAL p.{id,postedOn}, PARTIAL a.{id,login,name}, PARTIAL t.{id,title,slug}, PARTIAL c.{id,name}');
        $builder->innerJoin('n.post', 'p');
        $builder->innerJoin('p.author', 'a');
        $builder->innerJoin('p.topic', 't');
        $builder->innerJoin('t.category', 'c');
        $builder->whereCriteria(['n.user' => $this->userManager->getCurrentUserId()]);

        if ($since) {
            $builder->whereCriteria(['n.id >' => $since]);
        }

        $builder->orderBy('n.id', 'ASC');

        return $builder->getQuery()->getResult();
    }


    public function dismiss(int $id) : void {
        $notification = $this->notifications->find($id);

        if ($notification && $notification->getUser()->getId() === $this->userManager->getCurrentUserId()) {
            $this->em->remove($notification);
            $this->em->flush();
        }
    }

    public function dismissOldDisplayedNotifications() : void {
        $this->notifications->createQuery('DELETE FROM App:Notification n WHERE n.displayed = TRUE AND n.createdOn < :until')
            ->setParameter('until', new \DateTimeImmutable('-2 days'))
            ->execute();
    }


    /**
     * @param int $level
     * @return User[]
     */
    public function getNotifiableUsers(int $level) : array {
        $mapping = new ResultSetMappingBuilder($this->em);
        $mapping->addRootEntityFromClassMetadata(User::class, 'u');

        $query = $this->em->createNativeQuery(
            'WITH "notify" AS (' .
                'SELECT "user_id" FROM "notifications" ' .
                'WHERE "displayed" = FALSE AND "level" = ?::int AND "created_on" < ?::timestamp ' .
                'GROUP BY "user_id"' .
            ') ' .
            'SELECT ' . $mapping . ' FROM "notify" "n" ' .
            'INNER JOIN "users" "u" ON "u"."id" = "n"."user_id"',
            $mapping
        );

        $query->setParameter(1, $level);
        $query->setParameter(2, new \DateTimeImmutable(/*'-4 minutes'*/));
        return $query->getResult();
    }

    /**
     * @param User $user
     * @param int $level
     * @return Post[]
     */
    public function dispatchNotifications(User $user, int $level) : array {
        $mapping = new ResultSetMapping();
        $mapping->addScalarResult('post_id', 'post_id', 'integer');

        $query = $this->em->createNativeQuery(
            'DELETE FROM "notifications" ' .
            'WHERE "displayed" = FALSE AND "level" = ?::int AND "user_id" = ?::int AND "created_on" < ?::timestamp ' .
            'RETURNING "post_id"',
            $mapping
        );

        $query->setParameter(1, $level);
        $query->setParameter(2, $user->getId());
        $query->setParameter(3, new \DateTimeImmutable());
        $ids = array_column($query->getArrayResult(), 'post_id');

        $builder = $this->em->createQueryBuilder();
        $builder->select('p, a, t, c');
        $builder->from(Post::class, 'p');
        $builder->innerJoin('p.author', 'a');
        $builder->innerJoin('p.topic', 't');
        $builder->innerJoin('t.category', 'c');
        $builder->whereCriteria(['p.id' => $ids]);
        $builder->orderBy('p.id', 'ASC');
        return $builder->getQuery()->getResult();
    }

}
