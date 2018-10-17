<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Category;
use App\Entity\Topic;
use App\Entity\User;
use App\ORM\Lookup\TopicLookup;
use Kdyby\Doctrine\EntityManager;


class TopicManager {
    use SluggableTrait;

    private $entityManager;

    private $topics;

    private $posts;


    public function __construct(EntityManager $entityManager, PostManager $posts) {
        $this->entityManager = $entityManager;
        $this->topics = $entityManager->getRepository(Topic::class);
        $this->posts = $posts;
    }


    public function lookup() : TopicLookup {
        return new TopicLookup([$this->topics, 'createQueryBuilder']);
    }


    public function createTopic(Category $category, User $user, string $title, string $text) : Topic {
        $this->entityManager->beginTransaction();

        try {
            $topic = new Topic($category, $user, $this->formatSlug($title), $title);
            $this->entityManager->persist($topic);
            $this->entityManager->flush();

            $this->posts->createPost($topic, $user, $text);

            $this->entityManager->commit();

            return $topic;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }


    public function updateTopic(Topic $topic, string $title, string $text) : void {
        $this->entityManager->beginTransaction();

        try {
            $topic->setTitle($title);
            $topic->setSlug($this->formatSlug($title));
            $this->entityManager->persist($topic);
            $this->entityManager->flush();

            $this->posts->savePostEdit($topic->getFirstPost(), $text);

            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }


    public function deleteTopic(Topic $topic) : void {
        $this->entityManager->remove($topic);
        $this->entityManager->flush();
    }

}
