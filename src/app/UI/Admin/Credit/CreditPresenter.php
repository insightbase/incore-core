<?php

namespace App\UI\Admin\Credit;

use App\Component\DropCore\ConsolePageEnum;
use App\Component\DropCore\ConsoleUrlBuilder;
use App\Component\DropCore\DropCoreEnvEnum;
use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\ParameterBag;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Presenter;

class CreditPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    public function __construct(
        private readonly Setting $settingModel,
        private readonly ConsoleUrlBuilder $consoleUrlBuilder,
        private readonly ParameterBag $parameterBag,
    ) {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     */
    public function actionDefault(string $page = ConsolePageEnum::Credits->value): void
    {
        $activePage = ConsolePageEnum::tryFromString($page);
        if (null === $activePage) {
            throw new BadRequestException();
        }

        /** @var ?SettingEntity $setting */
        $setting = $this->settingModel->getDefault();
        $token = $setting?->dropcore_identity_token;
        $env = $setting?->dropcore_env;

        $this->template->activePage = $activePage;
        $this->template->pages = ConsolePageEnum::cases();
        $this->template->hasToken = null !== $token && '' !== $token;
        $this->template->consoleUrl = $this->template->hasToken
            ? $this->consoleUrlBuilder->build($token, $activePage, $env)
            : null;
        $this->template->demoOnProduction = !$this->parameterBag->debugMode
            && DropCoreEnvEnum::Demo->value === $env;
    }
}
