<?php

namespace App\UI\Setting;

use App\Component\EncryptFacade;
use App\Component\Mail\Exception\SystemNameNotFoundException;
use App\Component\Mail\SenderFactory;
use App\Component\Mail\SystemNameEnum;
use App\Model\Entity\SettingEntity;
use App\Model\Setting;
use Nette\Database\Table\ActiveRow;
use Nette\Mail\SendException;

readonly class SettingFacade
{
    public function __construct(
        private Setting $settingModel,
        private SenderFactory $senderFactory,
        private EncryptFacade $encryptFacade,
    ) {}

    /**
     * @param ?SettingEntity $setting
     */
    public function update(?ActiveRow $setting, Form\EditFormData $data): void
    {
        $updateData = (array) $data;
        if (null === $data->smtp_password) {
            unset($updateData['smtp_password']);
        } else {
            $updateData['smtp_password'] = $this->encryptFacade->encrypt($data->smtp_password);
        }

        if (null === $setting) {
            $this->settingModel->insert($updateData);
        } else {
            $setting->update($updateData);
        }
    }

    /**
     * @throws SystemNameNotFoundException
     * @throws SendException
     */
    public function testEmail(Form\TestEmailFormData $data): void
    {
        $sender = $this->senderFactory->create(SystemNameEnum::TestEmail->value);
        $sender->addTo($data->email);
        $sender->addModifier('message', $data->message);
        $sender->send();
    }
}
