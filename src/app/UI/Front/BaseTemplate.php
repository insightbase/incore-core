<?php

namespace App\UI\Front;

use App\Component\File\FileControl;
use App\Component\Image\ImageControl;
use App\Model\Entity\SettingEntity;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Table\ActiveRow;

class BaseTemplate extends Template
{
    /**
     * @var ?SettingEntity
     */
    public ?ActiveRow $setting;
    public ImageControl $imageControl;
    public FileControl $fileControl;
}