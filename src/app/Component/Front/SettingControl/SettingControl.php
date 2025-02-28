<?php

namespace App\Component\Front\SettingControl;

use App\Component\Translator\Translator;
use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;
use Nette\Application\UI\Control;
use Nette\Database\Table\ActiveRow;

class SettingControl extends Control
{
    /**
     * @var ?SettingEntity
     */
    private ?ActiveRow $setting;

    public function __construct(
        private readonly Setting    $settingModel,
    )
    {
        $this->setting = $this->settingModel->getDefault();
    }

    public function render(string $column):void
    {
        $value = '';
        if($this->setting !== null){
            $value = $this->setting->{$column};
        }
        $this->template->value = $value;
        $this->template->render(dirname(__FILE__) . '/default.latte');
    }
}