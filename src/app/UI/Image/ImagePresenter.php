<?php

namespace App\UI\Image;

use App\Component\Image\ImageFacade;
use App\UI\Accessory\ParameterBag;
use App\UI\Accessory\PresenterTrait\RequireLoggedUserTrait;
use Nette\Application\UI\Presenter;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;

class ImagePresenter extends Presenter
{
    use RequireLoggedUserTrait;

    public function __construct(
        private ParameterBag $parameterBag,
        private ImageFacade $imageFacade,
    )
    {
    }

    public function actionPreview(string $file, ?int $width = null, ?int $height = null): void
    {
        if(!file_exists($this->imageFacade->getPreviewName($file, $width, $height))) {
            $image = $this->imageFacade->generatePreview($file, $width, $height);
        } else {
            $image = Image::fromFile($this->imageFacade->getPreviewName($file, $width, $height));
        }

        $image->send();
    }

    public function actionUpload():void
    {
        $file = ($this->getHttpRequest()->getFile('file'));
        if($file instanceof FileUpload) {
            if ($file->isOk()) {
                $fileName = time() . '_' . $file->getSanitizedName();
                FileSystem::createDir($this->parameterBag->uploadDir);
                $file->move($this->parameterBag->uploadDir . '/' . $fileName);
                $this->payload->file = $fileName;
            } else {
                $this->payload->error = $this->translator->translate('Soubor se nepodařilo uložit');
            }
        } else {
            $this->payload->error = $this->translator->translate('Interní chyba');
        }
        $this->sendPayload();
    }
}