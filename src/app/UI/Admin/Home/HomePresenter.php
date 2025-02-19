<?php

declare(strict_types=1);

namespace App\UI\Admin\Home;

use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use Nette;

final class HomePresenter extends Nette\Application\UI\Presenter
{
    use StandardTemplateTrait;
    use RequireLoggedUserTrait;
}
