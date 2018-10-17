<?php

declare(strict_types=1);

namespace App\UI\Routing;

use Kdyby\Doctrine\EntityRepository;


class EntityMapper {

    private $repository;

    private $identifier;

    private $param;

    private $slug;


    public function __construct(EntityRepository $repository, string $identifier = 'id', bool $slug = true) {
        $this->repository = $repository;
        $this->identifier = $identifier;
        $this->param = lcfirst(preg_replace('~^.+\\\\~', '', $repository->getClassName()));
        $this->slug = $slug;
    }

    public function filterIn(array $params) : array {
        $identifier = $params[$this->identifier];
        $slug = $params['slug'] ?? null;
        unset($params[$this->identifier], $params['slug']);

        $params[$this->param] = $this->repository->findOneBy([
            $this->identifier => $identifier,
        ]);

        if ($this->slug && !$slug) {
            $params['action'] = 'permalink';
        }

        return $params;
    }

    public function filterOut(array $params) : array {
        $entity = $params[$this->param] ?? null;
        $action = $params['action'] ?? 'default';
        $method = 'get' . ucfirst($this->identifier);
        $params[$this->identifier] = $entity ? $entity->$method() : null;
        unset($params[$this->param]);

        if ($entity && $this->slug && $action !== 'permalink') {
            $params['slug'] = $entity->getSlug();
        }

        if ($action === 'permalink') {
            $params['action'] = 'default';
        }

        return $params;
    }
}
