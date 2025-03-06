<?php

namespace App\UI\Front;

use App\Component\Image\ImageControl;
use App\Model\Entity\SettingEntity;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Table\ActiveRow;

class BaseTemplate extends Template
{
    /**
     * @var ?SettingEntity
     */
    public ?ActiveRow $setting;
    public ImageControl $imageControl;
}