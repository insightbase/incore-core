<?php

namespace App\UI\Admin\Credit;

use App\Component\Credit\CreditFacade;
use App\Component\DropCore\ConsolePageEnum;
use App\Component\DropCore\ConsoleUrlBuilder;
use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use Nette\Application\BadRequestException;
use Nette\Application\ForbiddenRequestException;
use Nette\Application\UI\Presenter;

class CreditPresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    public function __construct(
        private readonly Setting $settingModel,
        private readonly ConsoleUrlBuilder $consoleUrlBuilder,
        private readonly CreditFacade $creditFacade,
    ) {
        parent::__construct();
    }

    /**
     * @throws BadRequestException
     * @throws ForbiddenRequestException
     */
    public function actionDefault(string $page = ConsolePageEnum::Buy->value): void
    {
        if (!$this->getUser()->isAllowed('credit', 'default')) {
            throw new ForbiddenRequestException();
        }

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
    }

    /**
     * @throws ForbiddenRequestException
     */
    public function actionBalance(): void
    {
        if (!$this->getUser()->isAllowed('credit', 'default')) {
            throw new ForbiddenRequestException();
        }

        $this->sendJson(['value' => $this->creditFacade->getBalance()]);
    }
}
