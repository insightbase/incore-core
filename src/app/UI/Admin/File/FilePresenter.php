<?php

namespace App\UI\Admin\File;

use App\Model\Admin\File;
use App\Model\Admin\Setting;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\ParameterBag;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Presenter;
use Nette\Http\FileUpload;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;
use Nette\Utils\Random;

class FilePresenter extends Presenter
{
    use RequireLoggedUserTrait;
    use StandardTemplateTrait;

    public function __construct(
        private readonly File         $fileModel,
        private readonly ParameterBag $parameterBag,
        private readonly Setting      $settingModel,
    )
    {
        parent::__construct();
    }

    #[NoReturn] public function actionDownload(int $id):void
    {
        $file = $this->fileModel->get($id);
        if($file === null){
            $this->error($this->translator->translate('flash_fileNotFound'));
        }
        $this->sendResponse(new FileResponse($this->parameterBag->uploadDir . '/' . $file->saved_name, $file->original_name));
    }

    #[NoReturn] public function actionUpload(): void
    {
        $file = $this->getHttpRequest()->getFile('file');
        if ($file instanceof FileUpload) {
            if ($file->isOk()) {
                $suffix = Arrays::last(explode('.', $file->getSanitizedName()));

                $fileName = md5(time() . '_' . Random::generate()) . '.' . $suffix;

                $fileRow = $this->fileModel->insert([
                    'original_name' => $file->getUntrustedName(),
                    'saved_name' => $fileName,
                ]);

                FileSystem::createDir($this->parameterBag->uploadDir);
                if($file->isImage()) {
                    $netteImage = $file->toImage();
                    $setting = $this->settingModel->getDefault();
                    if($setting?->max_image_resolution !== null){
                        $netteImage->resize($setting->max_image_resolution, $setting->max_image_resolution, Image::ShrinkOnly);
                    }
                    $netteImage->save($this->parameterBag->uploadDir . '/' . $fileName);
                }else{
                    $file->move($this->parameterBag->uploadDir . '/' . $fileName);
                }
                $this->payload->file = $fileName;
                $this->payload->fileId = $fileRow->id;
            } else {
                $this->payload->error = $this->translator->translate('flash_fileSaveFailed');
            }
        } else {
            $this->payload->error = $this->translator->translate('flash_internalError');
        }
        $this->sendPayload();
    }
}