<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(name="accounts")
 *
 * @property-read int $id
 * @property-read array $roles
 * @property-read \DateTimeImmutable|null $lastLogin
 * @property-read bool $subscribeMentions
 * @property-read User $user
 */
class Account {
    use SmartObject;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="password_hash", type="string", length=127)
     * @var string
     */
    private $passwordHash;

    /**
     * @ORM\Column(name="roles", type="simple_array", nullable=true)
     * @var string[]
     */
    private $roles;

    /**
     * @ORM\Column(name="last_login", type="datetime_immutable", nullable=true)
     * @var \DateTimeImmutable
     */
    private $lastLogin;

    /**
     * @ORM\Column(name="subscribe_mentions", type="boolean")
     * @var bool
     */
    private $subscribeMentions;

    /**
     * @ORM\OneToOne(targetEntity="User", mappedBy="account")
     * @var User
     */
    private $user;



    public function __construct(string $password, $roles = null, bool $subscribeMentions = true) {
        $this->setPassword($password);
        $this->setRoles($roles);
        $this->subscribeMentions = $subscribeMentions;
    }

    public function getId() : int {
        return $this->id;
    }

    public function getRoles() : array {
        return $this->roles ?: [];
    }

    public function getLastLogin() : ?\DateTimeImmutable {
        return $this->lastLogin;
    }

    public function isSubscribeMentions() : bool {
        return $this->subscribeMentions;
    }

    public function getUser() : User {
        return $this->user;
    }




    public function isPasswordValid(string $password) : bool {
        return password_verify($password, $this->passwordHash);
    }

    public function setPassword(string $password) : void {
        $this->passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function setRoles($roles) : void {
        if (!$roles) {
            $this->roles = null;
        } else if (!is_array($roles)) {
            $this->roles = explode(',', $roles);
        } else {
            $this->roles = $roles;
        }
    }

    public function setSubscribeMentions(bool $subscribe = true) : void {
        $this->subscribeMentions = $subscribe;
    }

    public function loggedIn() : void {
        $this->lastLogin = new \DateTimeImmutable();
    }


    public function injectUser(User $user) {
        if ($this->user) {
            throw new \RuntimeException('Cannot reassign an account\'s user');
        }

        $this->user = $user;
    }

}
