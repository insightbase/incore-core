<?php

namespace App\UI\MyAccount;

use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use Nette\Application\UI\Presenter;

class MyAccountPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;
}