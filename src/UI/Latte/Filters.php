<?php

declare(strict_types=1);

namespace App\UI\Latte;

use App\ORM\Manager\AvatarManager;
use App\UI\IAvatarOwner;


class Filters {

    private $avatarManager;


    public function __construct(AvatarManager $avatarManager) {
        $this->avatarManager = $avatarManager;
    }


    public function removeMdQuotes(string $md) : string {
        return preg_replace('/^\h*(?:>.+|(```|~~~)[\s\S]*?\\1)|(\s)\s+/m', '$2', $md);
    }

    public function formatDate(\DateTimeInterface $date, bool $withTimezone = false) : string {
        static $now;
        static $today;
        static $yesterday;
        static $year;

        if (!isset($now)) {
            $now = new \DateTimeImmutable();
            $today = $now->format('Y-m-d');
            $yesterday = (new \DateTimeImmutable('-1 day'))->format('Y-m-d');
            $year = $now->format('Y');
        }

        $m = floor(($now->getTimestamp() - $date->getTimestamp()) / 60);

        if ($m < 5) {
            return 'a minute ago';
        } else if ($m < 60) {
            return sprintf('%d minutes ago', $m);
        } else if ($m <= 180) {
            return sprintf('%d hours ago', floor($m / 60));
        } else {
            $dt = $date->format('Y-m-d');
            $tz = $withTimezone ? ' GMT' : '';

            if ($dt === $today) {
                return sprintf('today at %s%s', $date->format('H:i'), $tz);
            } else if ($dt === $yesterday) {
                return sprintf('yesterday at %s%s', $date->format('H:i'), $tz);
            } else if ($date->format('Y') === $year) {
                return $date->format('\o\n j. n. \a\t H:i') . $tz;
            } else {
                return $date->format('\o\n j. n. Y \a\t H:i') . $tz;
            }
        }
    }

    public function getAvatarUrl(IAvatarOwner $user, string $size = 'default') : string {
        return $this->avatarManager->getAvatarUrl($user, $size);
    }

}
