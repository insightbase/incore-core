<?php

namespace App\Component\Image;

use App\UI\Accessory\ParameterBag;
use Nette\Application\LinkGenerator;
use Nette\Utils\FileSystem;
use Nette\Utils\Html;
use Nette\Utils\Image;
use Nette\Utils\ImageColor;

class ImageFacade
{
    public function __construct(
        private ParameterBag $parameterBag,
        private LinkGenerator $linkGenerator,
    ) {}

    public function getPreviewName(string $fileName, ?int $width = null, ?int $height = null): string
    {
        $fileName = explode('_', $fileName);
        $newFileName = $fileName[0];
        unset($fileName[0]);
        $newFileName .= '_'.(null === $width ? 'null' : $width).'_'.(null === $height ? 'null' : $height).'_'.implode($fileName);

        return $newFileName;
    }

    public function preview(string $fileName, ?int $width = null, ?int $height = null): string
    {
        $previewFile = $this->parameterBag->previewDir.'/'.$this->getPreviewName($fileName, null === $width ? 0 : $width, null === $height ? 0 : $height);
        if (file_exists($previewFile)) {
            return $this->parameterBag->previewWwwDir.'/'.$this->getPreviewName($fileName, null === $width ? 0 : $width, null === $height ? 0 : $height);
        }

        return $this->linkGenerator->link('Image:preview', ['file' => $fileName, 'width' => $width, 'height' => $height]);
    }

    public function generatePreview(string $fileName, ?int $width = null, ?int $height = null): Image
    {
        if (!file_exists($this->parameterBag->uploadDir.'/'.$fileName)) {
            throw new Exception('File "'.$this->parameterBag->uploadDir.'/'.$fileName.'" not found');
        }
        $image = Image::fromFile($this->parameterBag->uploadDir.'/'.$fileName);
        if (null === $width && null === $height) {
            $image->save($this->parameterBag->previewDir.'/'.$this->getPreviewName($fileName, $width, $height));

            return $image;
        }
        $image->resize($width, $height, $image::ShrinkOnly);
        $image->sharpen();
        $image->saveAlpha(true);
        $container = Image::fromBlank($width, $height, ImageColor::rgb(255, 255, 255));
        $container->place($image, '50%', '50%');
        FileSystem::createDir($this->parameterBag->previewDir);
        $container->save($this->parameterBag->previewDir.'/'.$this->getPreviewName($fileName, $width, $height));

        return $container;
    }
}
