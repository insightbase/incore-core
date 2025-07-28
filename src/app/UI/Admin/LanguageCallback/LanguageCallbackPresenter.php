<?php

namespace App\UI\Admin\LanguageCallback;

use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Language\Exception\LanguageCallbackIdNotFoundException;
use App\UI\Admin\Language\Exception\LanguageIsDefaultException;
use App\UI\Admin\Language\Exception\LanguageNotFoundException;
use App\UI\Admin\Language\LanguageFacade;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\Presenter;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class LanguageCallbackPresenter extends Presenter
{
    public function __construct(
        private readonly LanguageFacade $languageFacade,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionTranslate(int $id):void
    {
        $this->getHttpResponse()->setContentType('application/json');
        $raw = $this->getHttpRequest()->getRawBody();
        $post = Json::decode($raw, true);

        if($post['valid']) {
            try {
                $this->languageFacade->processDropCoreCallback($id, $post);
                $status = 'success';
            } catch (LanguageCallbackIdNotFoundException $e) {
                $status = 'error';
                $this->payload->error = 'token id not found';
            } catch (LanguageIsDefaultException $e) {
                $status = 'error';
                $this->payload->error = 'language id default';
            } catch (LanguageNotFoundException $e) {
                $status = 'error';
                $this->payload->error = 'language not found';
            } catch (JsonException $e) {
                $status = 'error';
                $this->payload->error = 'json translate error';
            }
        }

        $this->payload->status = $status;
        $this->sendPayload();
    }
}