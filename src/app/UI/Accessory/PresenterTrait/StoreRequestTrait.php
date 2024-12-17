<?php

namespace App\UI\Accessory\PresenterTrait;

use Nette\Application\Attributes\Persistent;

trait StoreRequestTrait
{
    #[Persistent]
    public string $storeRequest = '';
}