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
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class LanguageCallbackPresenter extends Presenter
{
    public function __construct(
        private readonly LanguageFacade $languageFacade,
        private readonly ParameterBag   $parameterBag,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionTranslate(int $id):void
    {
        $raw = '{"valid":true,"id":"KLxCQYSyjWH8xCodKfhr","value":"[\n  {\n    \"translate_layout.title.sub\": \"Test text\",\n    \"enumeration_1\": \"test\",\n    \"contactForm_1\": \"First name\",\n    \"contactForm_2\": \"Last name\",\n    \"contactForm_3\": \"Email\"\n  }\n]"}';
//        $this->getHttpResponse()->setContentType('application/json');
//        $raw = $this->getHttpRequest()->getRawBody();
        $tempFile = $this->parameterBag->tempDir . '/language_callback_' . time();
        FileSystem::write($tempFile, $raw);
        try {
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
        } catch (JsonException $e) {
            FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
            $status = 'error';
        }

        $this->payload->status = $status;
        $this->sendPayload();
    }
}