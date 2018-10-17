<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\Entity\Category;
use Doctrine\ORM\NoResultException;
use Kdyby\Doctrine\EntityManager;


class CategoryManager {

    private $em;

    private $categories;

    private $autoSubscribed;


    public function __construct(EntityManager $em, array $autoSubscribed = []) {
        $this->em = $em;
        $this->categories = $em->getRepository(Category::class);
        $this->autoSubscribed = $autoSubscribed;
    }


    public function getById(int $id, bool $need = true) : ?Category {
        if ($category = $this->categories->find($id)) {
            return $category;
        } else if ($need) {
            throw new NoResultException();
        } else {
            return null;
        }
    }

    /**
     * @return Category[]
     */
    public function getCategoriesTree() : array {
        $all = $this->categories->findBy([], ['position' => 'ASC']);
        $root = [];

        foreach ($all as $category) {
            $category->markSubcategoriesAsLoaded();

            if (!$category->getParent()) {
                $root[] = $category;
            } else {
                $category->getParent()->addBatchLoadedSubcategory($category);
            }
        }

        return $root;
    }


    public function getAutoSubscribedCategories() : array {
        return !$this->autoSubscribed ? [] : $this->categories->findBy(['id' => $this->autoSubscribed], ['name' => 'ASC']);
    }

}
