<?php

namespace App\UI\Front;

use App\Component\File\FileControl;
use App\Component\Image\ImageControl;
use App\Model\Entity\LanguageEntity;
use App\Model\Entity\SettingEntity;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

class BaseTemplate extends Template
{
    /**
     * @var ?SettingEntity
     */
    public ?ActiveRow $setting;
    public ImageControl $imageControl;
    public FileControl $fileControl;
    /**
     * @var Selection<LanguageEntity>
     */
    public Selection $languages;
    /**
     * @var ActiveRow
     */
    public ActiveRow $defaultLanguage;
}