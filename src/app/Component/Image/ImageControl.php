<?php

namespace App\Component\Image;

use App\Component\Image\Exception\ImageNotFoundException;
use App\Component\Translator\Translator;
use App\Model\Admin\Image;
use App\Model\Admin\Setting;
use App\UI\Accessory\ParameterBag;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Attributes\Requires;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\Control;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\ImageException;
use Nette\Utils\UnknownImageFileException;

/**
 * @property ImageTemplate $template
 */
class ImageControl extends Control
{
    public function __construct(
        private readonly ImageFacade  $imageFacade,
        private readonly ParameterBag $parameterBag,
        private readonly Image        $imageModel,
        private readonly Translator   $translator,
        private readonly Storage      $storage,
        private readonly Setting      $settingModel,
    )
    {
    }

    public function existImage(int $id):bool
    {
        if($id === 0){
            return false;
        }
        $cache = new Cache($this->storage, 'image');
        $image = ImageDto::fromJson($cache->load('id_' . $id, function() use ($id):?string{
            $image = $this->imageModel->get($id);
            if($image === null){
                return null;
            }
            return json_encode(new ImageDto(
                saved_name: $image->saved_name,
            ));
        }));
        return file_exists($this->parameterBag->uploadDir . '/' . $image->saved_name);
    }

    protected function getImage(int $id):?ImageDto{
        $cache = new Cache($this->storage, 'image');
        $image = $cache->load('id_' . $id, function() use ($id):?string{
            $image = $this->imageModel->get($id);
            if($image === null){
                return null;
            }
            return json_encode(new ImageDto(
                saved_name: $image->saved_name,
            ));
        });
        if($image === null){
            return null;
        }else{
            return ImageDto::fromJson($image);
        }
    }

    public function getOriginal(?int $fileId):string{
        if($fileId === null){
            $fileId = $this->settingModel->getDefault()?->placeholder_id;
        }
        if($fileId === null){
            return '';
        }
        $image = $this->getImage($fileId);
        if($image === null){
            return '';
        }
        $previewName = 'orig_' . $this->imageFacade->getPreviewName($image);
        if(!file_exists($this->parameterBag->previewDir . '/' . $previewName)){
            if(!file_exists($this->parameterBag->uploadDir.'/'.$image->saved_name)){
                return '';
            }
            FileSystem::copy($this->parameterBag->uploadDir.'/'.$image->saved_name, $this->parameterBag->previewDir . '/' . $previewName);
        }
        return $this->parameterBag->previewWwwDir . '/' . $previewName;
    }

    public function getPreviewFile(?int $fileId, int $width, int $height, int $type = \Nette\Utils\Image::ShrinkOnly):string
    {
        if($fileId === null){
            $fileId = $this->settingModel->getDefault()?->placeholder_id;
        }
        if($fileId === null){
            return '';
        }
        $image = $this->getImage($fileId);
        $suffix = Arrays::last(explode('.', $image->saved_name));
        if($suffix === 'svg'){
            $previewName = $this->imageFacade->getPreviewName($image);
            if (!file_exists($this->parameterBag->previewDir . '/' . $previewName)) {
                FileSystem::copy($this->parameterBag->uploadDir.'/'.$image->saved_name, $this->parameterBag->previewDir . '/' . $previewName);
            }
        }else {
            $previewName = $this->imageFacade->getPreviewName($image, $width, $height, $type);
            if (!file_exists($this->parameterBag->previewDir . '/' . $previewName)) {
                $this->imageFacade->generatePreview($image, $width, $height, $type)?->save($this->parameterBag->previewDir . '/' . $previewName);
            }
        }
        return $this->parameterBag->previewWwwDir . '/' . $previewName;
    }

    /**
     * @throws ImageException
     * @throws UnknownImageFileException|ImageNotFoundException
     */
    private function getParams(int $fileId, int $width, int $height, ?string $class = null, bool $showSetting = false, array $htmlAttributes = [], int $type = \Nette\Utils\Image::ShrinkOnly):array{
        $image = $this->getImage($fileId);
        if($image === null){
            throw new ImageNotFoundException();
        }
        $file = $image->saved_name;

        $suffix = Arrays::last(explode('.', $file));
        $ret = [];
        if($suffix === 'svg'){
            $svg = Html::el('span');
            $svg->style('width', $width . 'px');
            $svg->style('height', $height . 'px');
            $svg->style('position', 'relative');
            $svg->style('display', 'inline-block');
            $svg->class($class);

            if(file_exists($this->parameterBag->uploadDir . '/' . $file)) {
                $svgFile = FileSystem::read($this->parameterBag->uploadDir . '/' . $file);
                $svg->addHtml($svgFile);
            }
            $ret['svg'] = (string)$svg;
            $ret['image'] = null;
        }else{
            $ret['svg'] = null;
            $previewName = $this->imageFacade->getPreviewName($image, $width, $height, $type);
            if(!file_exists($this->parameterBag->previewDir . '/' . $previewName)){
                $ret['image'] = $this->imageFacade->generatePreview($image, $width, $height, $type);
            }
            $ret['imageFile'] = $this->parameterBag->previewWwwDir . '/' . $previewName;;
        }
        $ret['width'] = $width;
        $ret['height'] = $height;
        $ret['class'] = $class;
        $ret['fileId'] = $fileId;
        $ret['showSetting'] = $showSetting;
        $ret['control'] = $this;
        $ret['htmlAttributes'] = $htmlAttributes;
        return $ret;
    }

    public function renderOriginal(?int $fileId, ?string $class = null, array $htmlAttributes = []):void
    {
        if($fileId === null){
            $fileId = $this->settingModel->getDefault()?->placeholder_id;
        }
        if($fileId !== null) {
            $this->template->setTranslator($this->translator);
            $this->template->render(dirname(__FILE__) . '/original.latte', [
                'class' => $class,
                'htmlAttributes' => $htmlAttributes,
                'imageFile' => $this->getOriginal($fileId),
            ]);
        }
    }

    public function render(?int $fileId, int $width, int $height, ?string $class = null, bool $showSetting = false, array $htmlAttributes = [], int $type = \Nette\Utils\Image::ShrinkOnly):void
    {
        if($fileId === null){
            $fileId = $this->settingModel->getDefault()?->placeholder_id;
        }
        if($fileId !== null) {
            $this->template->setTranslator($this->translator);
            try {
                $this->template->render(dirname(__FILE__) . '/default.latte', $this->getParams($fileId, $width, $height, $class, $showSetting, $htmlAttributes, $type));
            } catch (ImageNotFoundException|UnknownImageFileException $e) {

            }
        }
    }

    public function renderToString(?int $fileId, int $width, int $height, ?string $class = null, bool $showSetting = true):string
    {
        if($fileId === null){
            $fileId = $this->settingModel->getDefault()?->placeholder_id;
        }
        if($fileId !== null) {
            $this->template->setTranslator($this->translator);
            try {
                return $this->template->renderToString(dirname(__FILE__) . '/default.latte', $this->getParams($fileId, $width, $height, $class, $showSetting));
            } catch (ImageNotFoundException|UnknownImageFileException $e) {
                return '';
            }
        }
    }
}