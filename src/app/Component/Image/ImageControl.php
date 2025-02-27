<?php

namespace App\Component\Image;

use App\Component\Translator\Translator;
use App\Model\Admin\Image;
use App\UI\Accessory\ParameterBag;
use JetBrains\PhpStorm\NoReturn;
use Nette\Application\Attributes\Requires;
use Nette\Application\UI\Control;
use Nette\Utils\Arrays;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;

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
    )
    {
    }

    public function getPreviewFile(int $fileId, int $width, int $height):string
    {
        $image = $this->imageModel->get($fileId);
        $previewName = $this->imageFacade->getPreviewName($image, $width, $height);
        if(!file_exists($this->parameterBag->previewDir . '/' . $previewName)){
            $this->imageFacade->generatePreview($image, $width, $height)->save($this->parameterBag->previewDir . '/' . $previewName);
        }
        return '/images/' . $previewName;
    }

    private function getParams(int $fileId, int $width, int $height, ?string $class = null, bool $showSetting = false, array $htmlAttributes = []):array{
        $image = $this->imageModel->get($fileId);
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

            $svgFile = FileSystem::read($this->parameterBag->uploadDir . '/' . $file);
            $svg->addHtml($svgFile);
            $ret['svg'] = (string)$svg;
            $ret['image'] = null;
        }else{
            $ret['svg'] = null;
            $previewName = $this->imageFacade->getPreviewName($image, $width, $height);
            if(file_exists($this->parameterBag->previewDir . '/' . $previewName)){
                $ret['imageFile'] = '/images/' . $previewName;
            }else {
                $ret['image'] = $this->imageFacade->generatePreview($image, $width, $height);
            }
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

    public function render(int $fileId, int $width, int $height, ?string $class = null, bool $showSetting = false, array $htmlAttributes = []):void
    {
        $this->template->setTranslator($this->translator);
        $this->template->render(dirname(__FILE__) . '/default.latte', $this->getParams($fileId, $width, $height, $class, $showSetting, $htmlAttributes));
    }

    public function renderToString(int $fileId, int $width, int $height, ?string $class = null, bool $showSetting = true):string
    {
        $this->template->setTranslator($this->translator);
        return $this->template->renderToString(dirname(__FILE__) . '/default.latte', $this->getParams($fileId, $width, $height, $class, $showSetting));
    }
}