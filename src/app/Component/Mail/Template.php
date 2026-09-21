<?php

namespace App\Component\Mail;

use App\Component\Image\ImageControl;
use App\Model\Entity\SettingEntity;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;

class Template extends \Nette\Bridges\ApplicationLatte\Template
{
    /**
     * @var SettingEntity
     */
    public ActiveRow $setting;
    public EmailDto $email;
    public ImageControl $imageControl;
    public LinkGenerator $linkGenerator;
}