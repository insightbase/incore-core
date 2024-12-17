<?php

declare(strict_types=1);

namespace App\UI\Home;

use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\PresenterTrait\StandardTemplateTrait;
use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;
}
