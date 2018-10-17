<?php

declare(strict_types=1);

namespace App\Entity;

use App\UI\IAvatarOwner;
use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(name="users")
 *
 * @property-read int $id
 * @property-read string $login
 * @property-read string $name
 * @property-read string $email
 * @property-read string $role
 * @property-read int $avatarVersion
 * @property-read Account $account
 */
class User implements IAvatarOwner {
    use SmartObject;

    /**
     * @ORM\OneToOne(targetEntity="Account", inversedBy="user")
     * @ORM\JoinColumn(name="id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Account
     */
    private $account;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="NONE")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="login", type="string", length=127, unique=true)
     * @var string
     */
    private $login;

    /**
     * @ORM\Column(name="name", type="string", length=255)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="email", type="string", length=255, unique=true)
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="avatar_version", type="integer", nullable=true)
     * @var int
     */
    private $avatarVersion;

    /**
     * @ORM\Column(name="role", type="string", length=42, nullable=true)
     * @var string|null
     */
    private $role;


    public function __construct(Account $account, string $login, string $name, string $email, ?string $role = null) {
        $this->account = $account;
        $this->id = $account->getId();
        $this->login = $login;
        $this->name = $name;
        $this->email = $email;
        $this->role = $role;
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

    public function getRole() : ?string {
        return $this->role;
    }

    public function getAccount() : Account {
        return $this->account;
    }



    public function setName(string $name) : void {
        if (empty($name)) {
            throw new \InvalidArgumentException('Name cannot be empty');
        }

        $this->name = $name;
    }

    public function setEmail(string $email) : void {
        if (empty($email)) {
            throw new \InvalidArgumentException('E-mail address cannot be empty');
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid e-mail address: ' . $email);
        }

        $this->email = $email;
    }

    public function avatarUpdated() : void {
        $this->avatarVersion = $this->avatarVersion ? $this->avatarVersion + 1 : 1;
    }

    public function setRole(?string $role = null) : void {
        $this->role = $role;
    }

}
