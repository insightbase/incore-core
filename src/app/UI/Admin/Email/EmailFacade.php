<?php

namespace App\UI\Admin\Email;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Model\Admin\Email;
use App\Model\Admin\EmailLanguage;
use App\Model\Admin\Language;
use App\Model\Entity\EmailEntity;
use App\UI\Accessory\Admin\Form\Form as AdminForm;

readonly class EmailFacade
{
    public function __construct(
        private LogFacade $logFacade,
        private Email $emailModel,
        private Language $languageModel,
        private EmailLanguage $emailLanguageModel,
    )
    {
    }

    /**
     * @param EmailEntity $email
     * @return void
     */
    public function delete(\Nette\Database\Table\ActiveRow $email):void
    {
        $id = $email->id;
        $email->delete();
        $this->logFacade->create(LogActionEnum::Deleted, 'email', $id);
    }

    /**
     * @param EmailEntity $email
     * @param Form\EditFormData $data
     * @param AdminForm $form
     * @return void
     */
    public function update(\Nette\Database\Table\ActiveRow $email, Form\EditFormData $data, AdminForm $form):void
    {
        $email->update((array)$data);
        $this->saveTranslates($email->id, $form);
        $this->logFacade->create(LogActionEnum::Updated, 'email', $email->id);
    }

    public function create(Form\NewFormData $data, AdminForm $form):void
    {
        $email = $this->emailModel->insert((array)$data);
        if (null !== $email) {
            $this->saveTranslates($email->id, $form);
        }
        $this->logFacade->create(LogActionEnum::Created, 'email', $email?->id);
    }

    private function saveTranslates(int $emailId, AdminForm $form):void
    {
        foreach ($this->languageModel->getToTranslateNotDefault() as $language) {
            $translates = $form->getTranslates($language);
            $languageData = [
                'subject' => $translates['subject'] ?? null,
                'text' => $translates['text'] ?? null,
            ];
            $emailLanguage = $this->emailLanguageModel->getByEmailIdAndLanguage($emailId, $language);
            if (null === $emailLanguage) {
                $this->emailLanguageModel->insert($languageData + [
                    'email_id' => $emailId,
                    'language_id' => $language->id,
                ]);
            } else {
                $emailLanguage->update($languageData);
            }
        }
    }
}
