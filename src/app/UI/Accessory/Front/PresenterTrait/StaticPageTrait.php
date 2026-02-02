<?php

namespace App\UI\Accessory\Front\PresenterTrait;

use App\Component\Front\StaticPageControl\StaticPageControl;
use App\Component\Front\StaticPageControl\StaticPageControlFactory;
use Nette\DI\Attributes\Inject;

trait StaticPageTrait
{
    #[Inject]
    public StaticPageControlFactory $staticPageControlFactory;

    public function createComponentStaticPage():StaticPageControl
    {
        return $this->staticPageControlFactory->create();
    }
}