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
        $tempFile = $this->parameterBag->tempDir . '/language_callback_' . time();

        if($this->getParameter('file') !== null){
            $raw = $this->parameterBag->tempDir . '/' . $this->getParameter('file');
        }else {
            $this->getHttpResponse()->setContentType('application/json');
            $raw = $this->getHttpRequest()->getRawBody();
            FileSystem::write($tempFile, $raw);
        }
        try {
            $post = Json::decode($raw, true);
            if($post['valid']) {
                try {
                    $this->languageFacade->processDropCoreCallback($id, $post);
                    $status = 'success';
                } catch (LanguageCallbackIdNotFoundException $e) {
                    $status = 'error';
                    $this->payload->error = 'token id not found';
                    FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
                } catch (LanguageIsDefaultException $e) {
                    $status = 'error';
                    $this->payload->error = 'language id default';
                    FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
                } catch (LanguageNotFoundException $e) {
                    $status = 'error';
                    $this->payload->error = 'language not found';
                    FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
                } catch (JsonException $e) {
                    $status = 'error';
                    $this->payload->error = 'json translate error';
                    FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
                }catch (\Exception $e){
                    $status = 'error';
                    $this->payload->error = $e->getMessage();
                    FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
                }
            }
        } catch (JsonException $e) {
            FileSystem::write($tempFile, $raw . ', error: ' . $e->getMessage());
            $status = 'error';
            $this->payload->error = 'json translate error';
        }

        $this->payload->status = $status;
        $this->sendPayload();
    }
}