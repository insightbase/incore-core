<?php

namespace App\UI\Admin\Log;

use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use Nette\Application\UI\Presenter;

class LogPresenter extends Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;
}