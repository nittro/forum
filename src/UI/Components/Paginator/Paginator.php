<?php

declare(strict_types=1);

namespace App\UI\Components\Paginator;

use App\UI\BaseControl;
use Nette\Utils\Html;


class Paginator extends BaseControl {

    /** @var callable[] */
    public $onPageChange = [];

    private $buttonLabels = ['previous', 'next'];

    private $hide = null;

    private $containerSnippetName;

    private $itemSnippetName;

    private $pageCount;

    private $pageSize;

    private $page;


    public function __construct(int $itemCount, int $pageSize, int $page = 1) {
        parent::__construct();
        $this->pageCount = (int) ceil($itemCount / $pageSize);
        $this->pageSize = $pageSize;
        $this->page = $page;
    }

    public function setButtonLabels(string $previous, string $next) : void {
        $this->buttonLabels = [$previous, $next];
    }

    public function setHideClass(string $class) : void {
        $this->hide = $class;
    }

    public function setContainerSnippetName(string $name) : void {
        $this->containerSnippetName = $name;
    }

    public function setItemSnippetName(string $name) : void {
        $this->itemSnippetName = $name;
    }

    public function willRender() : bool {
        return $this->pageCount > 1;
    }

    public function hasPrevious() : bool {
        return $this->pageCount > 1 && $this->page > 1;
    }

    public function hasNext() : bool {
        return $this->pageCount > 1 && $this->page < $this->pageCount;
    }

    public function getOffset() : int {
        return ($this->page - 1) * $this->pageSize;
    }

    public function getLimit() : int {
        return $this->pageSize;
    }

    public function handleLoad(int $page) : void {
        $this->page = $page;
        $this->onPageChange($page);
    }

    public function renderBtnPrevious() : void {
        if ($this->hasPrevious()) {
            echo Html::el('a')
                ->setAttribute('href', $this->link('load!', ['page' => $this->page - 1]))
                ->setAttribute('class', 'text-muted ' . $this->getSnippetId('btn'))
                ->setAttribute('data-previous', 'true')
                ->setText($this->buttonLabels[0])
                ->render();
        }
    }

    public function renderBtnNext() : void {
        if ($this->hasNext()) {
            echo Html::el('a')
                ->setAttribute('href', $this->link('load!', ['page' => $this->page + 1]))
                ->setAttribute('class', 'text-muted ' . $this->getSnippetId('btn'))
                ->setText($this->buttonLabels[1])
                ->render();
        }
    }

    public function render() : void {
        if ($this->willRender()) {
            $this->template->hide = $this->hide;
            $this->template->containerSnippetId = $this->getParent()->getSnippetId($this->containerId ?? 'list');
            $this->template->itemSnippetId = $this->getParent()->getSnippetId($this->itemSnippetName ?? 'item');
            $this->template->pageCount = $this->pageCount;
            $this->template->pageSize = $this->pageSize;
            $this->template->page = $this->page;
            parent::render();
        }
    }

}
