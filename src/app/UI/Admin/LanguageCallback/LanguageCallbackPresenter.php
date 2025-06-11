<?php

namespace App\UI\Admin\LanguageCallback;

use App\UI\Accessory\ParameterBag;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;

class LanguageCallbackPresenter extends Presenter
{
    public function __construct(
        private readonly ParameterBag $parameterBag,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionTranslate(int $id):void
    {
        file_put_contents($this->parameterBag->tempDir . '/params', json_encode($this->getParameters()));
        $this->payload->status = 'success';
        $this->sendPayload();
    }
}