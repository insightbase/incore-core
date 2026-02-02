<?php

namespace App\UI\Admin\Preview;

use App\Component\Image\ImageDto;
use App\UI\Accessory\Admin\PresenterTrait\StandardTemplateTrait;
use App\UI\Accessory\ParameterBag;
use Nette\Application\Responses\FileResponse;
use Nette\Application\UI\Presenter;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Image;

class PreviewPresenter extends Presenter
{
    use StandardTemplateTrait;

    public function __construct(
        private readonly ParameterBag $parameterBag,
    )
    {
        parent::__construct();
    }

    public function actionDefault(int $fileId, ?int $width = null, ?int $height = null, int $type = Image::OrBigger):void
    {
        /** @var ?ImageDto $imageDto */
        $imageDto = $this->getComponent('image')->getImage($fileId);
        if($imageDto === null){
            $this->error();
        }

        $suffix = Arrays::last(explode('.', $imageDto->saved_name));

        if($width === null && $height === null){
            $previewName = 'orig_' . $this->imageFacade->getPreviewName($imageDto);
            if (!file_exists($this->parameterBag->previewDir . '/' . $previewName)) {
                FileSystem::copy($this->parameterBag->uploadDir.'/'.$imageDto->saved_name, $this->parameterBag->previewDir . '/' . $previewName);
            }
            if($suffix === 'svg') {
                $this->sendResponse(new FileResponse($this->parameterBag->previewDir . '/' . $previewName));
            }else{
                $image = Image::fromFile($this->parameterBag->previewDir . '/' . $previewName);
                $image->send();
            }
        }

        if($suffix === 'svg'){
            $previewName = $this->imageFacade->getPreviewName($imageDto);
            if (!file_exists($this->parameterBag->previewDir . '/' . $previewName)) {
                FileSystem::copy($this->parameterBag->uploadDir.'/'.$imageDto->saved_name, $this->parameterBag->previewDir . '/' . $previewName);
            }
            $this->sendResponse(new FileResponse($this->parameterBag->previewDir . '/' . $previewName));
        }else {
            $previewName = $this->imageFacade->getPreviewName($imageDto, $width, $height, $type);
            if (!file_exists($this->parameterBag->previewDir . '/' . $previewName)) {
                $image = $this->imageFacade->generatePreview($imageDto, $width, $height, $type);
                $image?->save($this->parameterBag->previewDir . '/' . $previewName, 92);
            }else{
                $image = Image::fromFile($this->parameterBag->previewDir . '/' . $previewName);
            }
            $image->send();
        }
    }
}