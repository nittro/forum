<?php

declare(strict_types=1);

namespace App\UI;


trait FilteredControlTrait {

    private $filter = null;

    public function setFilter(?array $filter) : void {
        $this->filter = $filter;
    }

}
