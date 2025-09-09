<?php

namespace App\UI\Accessory\Admin\Form;

use App\Component\Translator\Translator;
use App\Model\Admin\FormHelp;
use App\Model\Admin\FormHelpLanguage;
use App\Model\Admin\Language;

readonly class FormFacade
{
    public function __construct(
        private FormHelp $formHelpModel,
        private Language $languageModel,
        private FormHelpLanguage $formHelpLanguageModel,
    )
    {
    }

    public function update(FormHelpData $data, string $presenter, Form $form):void
    {
        $formHelp = $this->formHelpModel->getByPresenterAndInputHtmlId($presenter, $data->input_html_id);
        $updateData = (array)$data;
        unset($updateData['input_html_id']);
        if($formHelp !== null){
            $formHelp->update($updateData);
        }else{
            $formHelp = $this->formHelpModel->insert($updateData + [
                'presenter' => $presenter,
                'input' => $data->input_html_id,
            ]);
        }

        foreach($this->languageModel->getToTranslateNotDefault() as $language) {
            $dataLanguage = $form->getTranslates($language);
            $formHelpLanguage = $this->formHelpLanguageModel->getByFormHelpAndLanguage($formHelp, $language);
            if($formHelpLanguage === null) {
                $this->formHelpLanguageModel->insert([
                    'language_id' => $language->id,
                    'form_help_id' => $formHelp->id
                ] + $dataLanguage);
            }else{
                $formHelpLanguage->update($dataLanguage);
            }
        }
    }
}