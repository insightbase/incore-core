<?php

namespace App\UI\Accessory\Admin\PresenterTrait;

use Nette\Application\Attributes\Persistent;

trait StoreRequestTrait
{
    #[Persistent]
    public string $storeRequest = '';
}
