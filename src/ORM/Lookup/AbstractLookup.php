<?php

declare(strict_types=1);

namespace App\ORM\Lookup;

use App\UI\Components\Paginator\Paginator;
use Kdyby\Doctrine\QueryBuilder;
use Nette\SmartObject;


abstract class AbstractLookup implements \IteratorAggregate, \Countable {
    use SmartObject;

    /** @var callable[] */
    public $onLoad = [];


    private $queryBuilderFactory;

    private $alias;

    private $indexBy = null;

    /** @var \Closure[] */
    private $commonModifiers = [];

    /** @var \Closure[] */
    private $selectModifiers = [];

    /** @var array */
    private $result;

    /** @var int */
    private $totalCount = null;


    public function __construct(callable $queryBuilderFactory, string $alias = 'o') {
        $this->queryBuilderFactory = $queryBuilderFactory;
        $this->alias = $alias;
    }


    public function getIterator() : \Generator {
        $this->loadResult();
        yield from $this->result;
    }

    public function toArray() : array {
        $this->loadResult();
        return $this->result;
    }

    public function extract(string $prop, ?string $key = null) : array {
        $this->loadResult();
        return array_column($this->result, $prop, $key);
    }

    public function count() : int {
        $this->loadResult();
        return count($this->result);
    }

    public function getTotalCount() : int {
        $this->loadCount();
        return $this->totalCount;
    }


    public function indexBy(string $property) : self {
        $this->indexBy = $property;
        return $this;
    }


    public function setMaxResults(int $results) : self {
        $this->addSelectModifier(function(QueryBuilder $builder) use ($results) : void {
            $builder->setMaxResults($results);
        });

        return $this;
    }

    public function applyPaginator(Paginator $paginator) : self {
        $this->addSelectModifier(function(QueryBuilder $builder) use ($paginator) : void {
            $builder->setFirstResult($paginator->getOffset());
            $builder->setMaxResults($paginator->getLimit());
        });

        return $this;
    }



    protected function addCommonModifier(\Closure $modifier) : void {
        $this->assertNotLoaded();
        $this->commonModifiers[] = $modifier;
    }

    protected function addSelectModifier(\Closure $modifier) : void {
        $this->assertResultNotLoaded();
        $this->selectModifiers[] = $modifier;
    }

    protected function createSelectQueryBuilder() : QueryBuilder {
        $builder = $this->createQueryBuilder();

        foreach ($this->selectModifiers as $modifier) {
            $modifier($builder);
        }

        return $builder;
    }

    protected function createCountQueryBuilder() : QueryBuilder {
        return $this->createQueryBuilder()
            ->select(sprintf('COUNT(%s.id)', $this->alias));
    }

    protected function createQueryBuilder() : QueryBuilder {
        $builder = call_user_func($this->queryBuilderFactory, $this->alias);

        if (!($builder instanceof QueryBuilder)) {
            throw new \RuntimeException('Invalid query builder factory, didn\'t return an instance of QueryBuilder');
        }

        foreach ($this->commonModifiers as $modifier) {
            $modifier($builder);
        }

        return $builder;
    }



    private function assertNotLoaded() : void {
        $this->assertResultNotLoaded();
        $this->assertTotalCountNotLoaded();
    }

    private function assertResultNotLoaded() : void {
        if (isset($this->result)) {
            throw new \RuntimeException('Cannot modify lookup object after it\'s been loaded');
        }
    }

    private function assertTotalCountNotLoaded() : void {
        if (isset($this->totalCount)) {
            throw new \RuntimeException('Cannot modify lookup object after it\'s been loaded');
        }
    }

    private function loadResult() : void {
        if (isset($this->result)) {
            return;
        }

        $this->result = $this->createSelectQueryBuilder()
            ->getQuery()
            ->getResult();

        if ($this->indexBy) {
            if (strpos($this->indexBy, '.') !== false) {
                list($prop1, $prop2) = explode('.', $this->indexBy);
                $keys = array_map(function($item) use ($prop1, $prop2) { return $item->{$prop1}->{$prop2}; }, $this->result);
                $this->result = array_combine($keys, $this->result);
            } else {
                $this->result = array_column($this->result, null, $this->indexBy);
            }
        }

        $this->onLoad($this->result);
    }

    private function loadCount() : void {
        if (isset($this->totalCount)) {
            return;
        }

        $this->totalCount = $this->createCountQueryBuilder()
            ->getQuery()
            ->getSingleScalarResult();
    }

}
