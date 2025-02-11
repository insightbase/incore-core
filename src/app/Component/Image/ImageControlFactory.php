<?php

namespace App\Component\Image;

use App\UI\Accessory\ParameterBag;
use Nette\Application\UI\Control;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;

/**
 * @property ImageTemplate $template
 */
class ImageControlFactory extends Control
{
    public function __construct(
        private readonly ImageFacade  $imageFacade,
        private readonly ParameterBag $parameterBag,
        private readonly LatteFactory $latteFactory,
    )
    {
    }

    private function getParams(string $file, int $width, int $height):array{
        $suffix = explode('.', $file);
        $suffix = $suffix[count($suffix) - 1];
        $ret = [];
        if($suffix === 'svg'){
            $svg = Html::el('span');
            $svg->style('width', $width . 'px');
            $svg->style('height', $height . 'px');
            $svg->style('position', 'relative');
            $svg->style('display', 'inline-block');
            $svg->addHtml(FileSystem::read($this->parameterBag->uploadDir . '/' . $file));
            $ret['svg'] = (string)$svg;
            $ret['image'] = null;
        }else{
            $ret['svg'] = null;
            $ret['image'] = $this->imageFacade->generatePreview($file, $width, $height);
        }
        $ret['width'] = $width;
        $ret['height'] = $height;
        return $ret;
    }

    public function render(string $file, int $width, int $height):void
    {
        $this->template->render(dirname(__FILE__) . '/default.latte', $this->getParams($file, $width, $height));
    }

    public function renderToString(string $file, int $width, int $height):string
    {
        $latte = $this->latteFactory->create();
        return $latte->renderToString(dirname(__FILE__) . '/default.latte', $this->getParams($file, $width, $height));
    }
}