<?php

declare(strict_types=1);

namespace App\UI;

use App\UI\Components\Paginator\Paginator;
use App\ORM\Lookup\AbstractLookup;


trait PagedControlTrait {

    private $paging = true;

    private $page = 1;

    private $pageSize = 20;

    private $containerSnippetName = 'list';

    private $itemSnippetName = 'item';

    private $pagingApplied = false;


    public function enablePaging(?int $size = null) : void {
        $this->paging = true;

        if (isset($size)) {
            $this->pageSize = $size;
        }
    }

    public function disablePaging() : void {
        $this->paging = false;
    }

    public function setPage(int $page) : void {
        $this->page = $page;
    }

    public function getPage() : int {
        return $this->page;
    }

    public function setPageSize(?int $size) : void {
        $this->pageSize = $size;
    }


    abstract protected function getResource() : AbstractLookup;

    abstract public function getPresenter($throw = true);

    abstract public function getComponent($name, $throw = true);

    protected function getPagedResource() : AbstractLookup {
        if (!$this->pagingApplied) {
            $this->pagingApplied = true;

            if ($this->paging) {
                $this->getResource()->applyPaginator($this->getComponent('page'));
            } else {
                $this->getResource()->setMaxResults($this->pageSize);
            }
        }

        return $this->getResource();
    }

    protected function setupPaging($template, ?string $previousButtonLabel = null, ?string $nextButtonLabel = null) : void {
        if ($this->paging) {
            $template->paging = [
                'first' => !$this->getComponent('page')->hasPrevious(),
                'last' => !$this->getComponent('page')->hasNext(),
            ];

            if ($previousButtonLabel || $nextButtonLabel) {
                $this->getComponent('page')->setButtonLabels($previousButtonLabel, $nextButtonLabel);
            }
        } else {
            $template->paging = [
                'first' => false,
                'last' => false,
            ];
        }
    }

    protected function setContainerSnippetName(string $name) : void {
        $this->containerSnippetName = $name;
    }

    protected function setItemSnippetName(string $name) : void {
        $this->itemSnippetName = $name;
    }


    private function pageChanged() : void {
        $this->redrawControl($this->containerSnippetName);
        $this->getPresenter()->payload->order = $this->getPagedResource()->extract('id');
    }

    public function createComponentPage() : Paginator {
        $control = new Paginator($this->getResource()->getTotalCount(), $this->pageSize, $this->page);
        $control->setContainerSnippetName($this->containerSnippetName);
        $control->setItemSnippetName($this->itemSnippetName);
        $control->setHideClass('parent');
        $control->onPageChange[] = \Closure::fromCallable([$this, 'pageChanged']);
        return $control;
    }

}
