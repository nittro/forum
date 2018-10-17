<?php

declare(strict_types=1);

namespace App\PublicModule\Presenters;

use Doctrine\ORM\NoResultException;
use Nette\Application\BadRequestException;
use Tracy\ILogger;


class ErrorPresenter extends BasePresenter {

    private $logger;


    public function __construct(ILogger $logger) {
        parent::__construct();
        $this->logger = $logger;
    }


    public function actionDefault(?\Throwable $exception = null) : void {
        if (!$exception) {
            $exception = new \RuntimeException();
        } else if ($exception instanceof NoResultException) {
            $exception = new BadRequestException();
        }

        if ($exception instanceof BadRequestException) {
            if ($exception->getCode() === 403) {
                $this->setView('@e403');
            } else {
                $this->setView('@e4xx');
            }
        } else {
            $this->setView('@e5xx');
            $this->logger->log($exception, ILogger::EXCEPTION);
        }
    }

}
