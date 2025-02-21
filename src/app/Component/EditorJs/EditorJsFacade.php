<?php

namespace App\Component\EditorJs;

use Nette\Utils\Html;

readonly class EditorJsFacade
{
    public function __construct(
        private EditorJsRendererFactory $rendererFactory,
    )
    {
    }

    public function renderJson(string $json):Html
    {
        return $this->rendererFactory->create()->render($json);
    }
}