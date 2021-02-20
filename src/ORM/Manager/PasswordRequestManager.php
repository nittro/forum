<?php

declare(strict_types=1);

namespace App\ORM\Manager;


use App\Entity\Account;
use App\Entity\PasswordRequest;
use Kdyby\Doctrine\EntityManager;
use Nette\Security\AuthenticationException;
use Nette\Utils\Random;

class PasswordRequestManager {

    private $em;

    private $requests;


    public function __construct(EntityManager $em) {
        $this->em = $em;
        $this->requests = $em->getRepository(PasswordRequest::class);
    }


    public function createRequest(Account $account) : PasswordRequest {
        $request = new PasswordRequest($account, $this->generateToken(), new \DateTimeImmutable('+48 hours'));
        $this->em->persist($request);
        $this->em->flush();
        return $request;
    }


    public function validateRequest(int $id, string $token) : PasswordRequest {
        $request = $this->requests->find($id);

        if (!$request || !$request->isValid($token)) {
            throw new AuthenticationException();
        }

        return $request;
    }


    public function removeRequest(PasswordRequest $request) : void {
        $this->em->remove($request);
        $this->em->flush();
    }


    public function purgeExpiredRequests() : void {
        $this->em->createQueryBuilder()
            ->delete(PasswordRequest::class, 'r')
            ->where('r.expiresOn < :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }


    private function generateToken() : string {
        return Random::generate(32, '0-9a-zA-Z.');
    }

}
