<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Category;
use App\Entity\CategorySubscription;
use Kdyby\Doctrine\EntityManager;


class CategorySubscriptionManager {

    private $em;

    private $userManager;

    private $subscriptions;


    /** @var CategorySubscription[] */
    private $cache = [];


    public function __construct(EntityManager $em, UserManager $userManager) {
        $this->em = $em;
        $this->userManager = $userManager;
        $this->subscriptions = $em->getRepository(CategorySubscription::class);
    }


    public function getSubscription(Category $category, bool $create = true) : ?CategorySubscription {
        if (!isset($this->cache[$category->getId()]) && ($create || !key_exists($category->getId(), $this->cache))) {
            $subscription = $this->subscriptions->findOneBy([
                'user' => $this->userManager->getCurrentUserId(),
                'category' => $category,
            ]);

            if (!$subscription && $create) {
                $subscription = new CategorySubscription($this->userManager->getCurrentUser(), $category);
            }

            $this->cache[$category->getId()] = $subscription;
        }

        return $this->cache[$category->getId()];
    }


    public function subscribe(Category $category, int $level) : void {
        $subscription = $this->getSubscription($category);
        $subscription->setNotificationLevel($level);
        $this->em->persist($subscription);
        $this->em->flush();
    }


    public function unsubscribe(Category $category) : void {
        if ($subscription = $this->getSubscription($category, false)) {
            $this->em->remove($subscription);
            $this->em->flush();
        }
    }

}
