<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Account;
use App\UI\IAvatarOwner;
use Nette\Security\IIdentity;
use Nette\SmartObject;


/**
 * @property-read int $id
 * @property-read string $login
 * @property-read string $name
 * @property-read string $email
 * @property-read int|null $avatarVersion
 * @property-read array $roles
 * @property-read bool $subscribeMentions
 * @property-read \DateTimeImmutable|null $lastLogin
 */
class Identity implements IIdentity, IAvatarOwner {
    use SmartObject;

    private $id;

    private $login;

    private $name;

    private $email;

    private $avatarVersion;

    private $roles;

    private $subscribeMentions;

    private $lastLogin;


    public function __construct(Account $account) {
        $this->id = $account->getUser()->getId();
        $this->login = $account->getUser()->getLogin();
        $this->name = $account->getUser()->getName();
        $this->email = $account->getUser()->getEmail();
        $this->avatarVersion = $account->getUser()->getAvatarVersion();
        $this->roles = $account->getRoles();
        $this->subscribeMentions = $account->isSubscribeMentions();
        $this->lastLogin = $account->getLastLogin();
    }

    public function getId() : int {
        return $this->id;
    }

    public function getLogin() : string {
        return $this->login;
    }

    public function getName() : string {
        return $this->name;
    }

    public function getEmail() : string {
        return $this->email;
    }

    public function hasAvatar() : bool {
        return isset($this->avatarVersion);
    }

    public function getAvatarVersion() : ?int {
        return $this->avatarVersion;
    }

    public function getRoles() : array {
        return $this->roles;
    }

    public function isSubscribeMentions() : bool {
        return $this->subscribeMentions;
    }

    public function getLastLogin() : ?\DateTimeImmutable {
        return $this->lastLogin;
    }

}
