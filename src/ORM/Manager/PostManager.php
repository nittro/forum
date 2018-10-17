<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Post;
use App\Entity\Topic;
use App\Entity\User;
use App\ORM\Lookup\PostLookup;
use App\ORM\Processor\PostProcessor;
use Doctrine\ORM\NoResultException;
use Kdyby\Doctrine\EntityManager;


class PostManager {

    private $entityManager;

    private $posts;

    private $postProcessor;

    private $notificationManager;


    public function __construct(
        EntityManager $entityManager,
        PostProcessor $postProcessor,
        NotificationManager $notificationManager
    ) {
        $this->entityManager = $entityManager;
        $this->posts = $entityManager->getRepository(Post::class);
        $this->postProcessor = $postProcessor;
        $this->notificationManager = $notificationManager;
    }


    public function lookup() : PostLookup {
        return new PostLookup([$this->posts, 'createQueryBuilder']);
    }


    public function getPost(int $id) : Post {
        $post = $this->posts->find($id);

        if (!$post) {
            throw new NoResultException();
        }

        return $post;
    }

    public function getCountPerTopic(array $ids) : array {
        $builder = $this->posts->createQueryBuilder('p');
        $builder->select('IDENTITY(p.topic) AS t, COUNT(p.id) AS c');
        $builder->whereCriteria(['p.topic' => $ids]);
        $builder->groupBy('p.topic');
        return array_column($builder->getQuery()->getArrayResult(), 'c', 't');
    }


    public function resolvePost(Topic $topic, int $id) : array {
        $builder = $this->posts->createQueryBuilder('p');
        $builder->select('COUNT(p.id) idx, MAX(p.id) id');
        $builder->whereCriteria([
            'p.topic' => $topic,
            'p.id <=' => $id,
        ]);
        $builder->groupBy('p.topic');
        return $builder->getQuery()->getSingleResult();
    }



    public function createPost(Topic $topic, User $user, string $text) : Post {
        $this->entityManager->beginTransaction();

        try {
            $post = new Post($topic, $user, $this->postProcessor->processPostContent($topic, $text), $text);
            $topic->addPost($post);

            $this->entityManager->persist($post);
            $this->entityManager->persist($topic);
            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        $this->notificationManager->createNotifications($post);

        return $post;
    }


    public function savePostEdit(Post $post, string $text) : void {
        $post->updateText($this->postProcessor->processPostContent($post->topic, $text), $text);
        $this->entityManager->persist($post);
        $this->entityManager->flush();
    }


    public function deletePost(Post $post) : void {
        if ($post->topic->firstPost->id === $post->id) {
            throw new \InvalidArgumentException("Cannot delete a topic's first post");
        }

        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

}
