<?php

declare(strict_types=1);

namespace App\PublicModule\Components\CategoryListControl;

use App\Entity\Category;
use App\ORM\Manager\CategoryManager;
use App\UI\BaseControl;


class CategoryListControl extends BaseControl {

    private $categoryManager;

    /** @var Category|null */
    private $rootCategory;

    private $full = true;


    public function __construct(CategoryManager $categoryManager) {
        parent::__construct();
        $this->categoryManager = $categoryManager;
    }


    public function setRootCategory(Category $category) : void {
        $this->rootCategory = $category;
        $this->full = false;
    }

    public function setShallow() : void {
        $this->full = false;
    }


    public function render() : void {
        $this->template->rootCategory = $this->rootCategory;
        $this->template->categories = $this->rootCategory
            ? $this->rootCategory->getSubcategories()
            : $this->categoryManager->getCategoriesTree();
        $this->template->full = $this->full;

        parent::render();
    }


}
