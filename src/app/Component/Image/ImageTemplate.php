<?php

namespace App\Component\Image;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Image;

class ImageTemplate extends Template
{
    public ?string $svg = null;
    public ?string $class = null;
    public ?Image $image = null;
    public int $width;
    public int $height;
    public int $fileId;
    public string $basicModalFile;
    public string $basicFormFile;
    public bool $showSetting;
    public ImageControl $control;
}