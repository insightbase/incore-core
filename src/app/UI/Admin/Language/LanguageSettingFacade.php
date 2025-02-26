<?php

namespace App\UI\Admin\Language;

use App\Model\Entity\LanguageSettingEntity;
use App\UI\Admin\Language\Form\LanguageSettingFormData;
use Nette\Database\Table\ActiveRow;

class LanguageSettingFacade
{
    /**
     * @param LanguageSettingEntity $languageSetting
     * @param LanguageSettingFormData $data
     * @return void
     */
    public function update(ActiveRow $languageSetting, LanguageSettingFormData $data):void
    {
        $languageSetting->update((array)$data);
    }
}