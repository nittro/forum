<?php

declare(strict_types=1);

namespace App\Security;

use Nette\StaticClass;


class Helpers {
    use StaticClass;


    public static function getLoginPattern() : string {
        return '[a-z0-9]+(?:[._]+[a-z0-9]+)*';
    }


    public static function getPasswordPattern() : string {
        return '(?:(?=.*[a-z])(?:(?=.*[A-Z])(?=.*[\d\W])|(?=.*\W)(?=.*\d))|(?=.*\W)(?=.*[A-Z])(?=.*\d)).{8,}';
    }

}
