<?php

declare(strict_types=1);

namespace App\Core;

use App\Model\Admin\Language;
use Nette;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    use Nette\StaticClass;

    public function __construct(
        private readonly Language $languageModel,
    ) {}

    public function createRouter(): RouteList
    {
        $languages = $this->languageModel->getToTranslate();
        $default = null;
        $langs = [];
        foreach ($languages as $language) {
            if ($language->is_default) {
                $default = $language;
            }
            $langs[] = $language->url;
        }

        $langPrefix = sprintf('[<lang=%s %s>/]', $default->url, implode('|', $langs));

        $router = new RouteList();

        $router
            ->withModule('Admin')
                ->addRoute($langPrefix.'admin/<presenter>/<action>[/<id>]', 'Home:default')
            ->end()
            ->withModule('Front')
            ->addRoute($langPrefix.'<presenter>/<action>[/<id>]', 'Home:default')
            ->end()
        ;

        return $router;
    }
}
