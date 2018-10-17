<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Nette\SmartObject;


/**
 * @ORM\Entity()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\Table(name="password_requests")
 *
 * @property-read int $id
 * @property-read string $token
 * @property-read Account $account
 * @property-read \DateTimeImmutable $expiresOn
 */
class PasswordRequest {
    use SmartObject;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    private $id;


    /**
     * @ORM\ManyToOne(targetEntity="Account", fetch="EAGER")
     * @ORM\JoinColumn(name="account_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @var Account
     */
    private $account;

    /**
     * intentionally not persisted
     * @var string
     */
    private $token;

    /**
     * @ORM\Column(name="token_hash", type="string", length=72)
     * @var string
     */
    private $tokenHash;

    /**
     * @ORM\Column(name="expires_on", type="datetime_immutable")
     * @var \DateTimeImmutable
     */
    private $expiresOn;




    public function __construct(Account $account, string $token, \DateTimeImmutable $expiresOn) {
        $this->account = $account;
        $this->token = $token;
        $this->tokenHash = password_hash($token, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->expiresOn = $expiresOn;
    }


    public function getId() : int {
        return $this->id;
    }

    public function getAccount() : Account {
        return $this->account;
    }

    public function getToken() : string {
        return $this->token;
    }

    public function getExpiresOn() : \DateTimeImmutable {
        return $this->expiresOn;
    }

    public function isValid(string $token) : bool {
        return password_verify($token, $this->tokenHash) && $this->expiresOn >= new \DateTimeImmutable();
    }

}
