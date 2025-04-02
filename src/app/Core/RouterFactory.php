<?php

declare(strict_types=1);

namespace App\Core;

use App\Model\Admin\Language;
use App\Model\Admin\LanguageSetting;
use App\Model\Enum\LanguageSettingTypeEnum;
use Nette;
use Nette\Application\Routers\RouteList;

final class RouterFactory
{
    use Nette\StaticClass;

    public function __construct(
        private readonly Language           $languageModel,
        private readonly LanguageSetting    $languageSettingModel,
        private readonly Nette\Http\Request $request,
    ) {}

    public function createRouter(): RouteList
    {
        $languageByHost = null;
        if($this->languageSettingModel->getSetting()->type === LanguageSettingTypeEnum::Host->value){
            $language = $this->languageModel->getByHost($this->request->getHeader('host'));
            if($language){
                $languageByHost = $language;
            }
        }
        if($languageByHost !== null){
            $langPrefix = sprintf('[<lang=%s>/]', $languageByHost->url);
        }else {
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
        }

        $router = new RouteList();

        $router
            ->withModule('Admin')
                ->addRoute('[<lang=cs cs>/]admin/<presenter>/<action>[/<id>]', 'Home:default')
            ->end()
            ->withModule('Front')
            ->addRoute($langPrefix.'<presenter>/<action>[/<id>]', 'Home:default')
            ->end()
        ;

        return $router;
    }
}
