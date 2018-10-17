<?php

declare(strict_types=1);

namespace App\UI;


interface IAvatarOwner {

    public function getId() : int;

    public function hasAvatar() : bool;

    public function getAvatarVersion() : ?int;

}
