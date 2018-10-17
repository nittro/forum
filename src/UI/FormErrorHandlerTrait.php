<?php

declare(strict_types=1);

namespace App\UI;


trait FormErrorHandlerTrait {

    abstract public function redrawControl($snippet = null, $redraw = true);

    private function getFormErrorHandler(string $snippet = 'form') : \Closure {
        return function () use ($snippet) : void {
            $this->redrawControl($snippet);
        };
    }

}
