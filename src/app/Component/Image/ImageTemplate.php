<?php

namespace App\Component\Image;

use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Image;

class ImageTemplate extends Template
{
    public ?string $svg = null;
    public ?Image $image = null;
    public int $width;
    public int $height;

}