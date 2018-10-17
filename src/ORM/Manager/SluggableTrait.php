<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use Nette\Utils\Strings;


trait SluggableTrait {

    public function formatSlug(string $name) : string {
        return Strings::webalize($name);
    }

}
