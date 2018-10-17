<?php

declare(strict_types=1);

namespace App\ORM\Manager;

use App\UI\IAvatarOwner;
use Nette\Utils\Image;


class AvatarManager {

    private const SIZES = [
        'default' => ['width' => 128, 'height' => 128],
        'icon' => ['width' => 32, 'height' => 32],
    ];

    private $storagePath;

    private $baseUrl;

    private $default;


    public function __construct(string $storagePath, string $baseUrl, string $default) {
        $this->storagePath = $storagePath;
        $this->baseUrl = $baseUrl;
        $this->default = $default;
    }


    public function getAvatarUrl(IAvatarOwner $user, string $size = 'default') : string {
        return $user->hasAvatar()
            ? sprintf('%s/%d/%d.%s.jpg', $this->baseUrl, $user->getId(), $user->getAvatarVersion(), $size)
            : $this->default;
    }


    public function saveAvatar(IAvatarOwner $user, Image $avatar) : void {
        @mkdir(sprintf('%s/%d', $this->storagePath, $user->getId()), 0755, true);

        foreach (self::SIZES as $size => $params) {
            $im = clone $avatar;
            $im->resize($params['width'], $params['height'], Image::EXACT);
            $im->save(sprintf('%s/%d/%d.%s.jpg', $this->storagePath, $user->getId(), $user->getAvatarVersion(), $size), 90, Image::JPEG);
        }
    }

}
