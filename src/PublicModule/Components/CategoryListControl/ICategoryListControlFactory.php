<?php

declare(strict_types=1);

namespace App\PublicModule\Components\CategoryListControl;


interface ICategoryListControlFactory {

    public function create() : CategoryListControl;

}
