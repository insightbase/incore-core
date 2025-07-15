<?php

namespace App\UI\Admin\Setting;

use App\Component\EncryptFacade;
use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Component\Mail\Exception\SystemNameNotFoundException;
use App\Component\Mail\SenderFactory;
use App\Component\Mail\SystemNameEnum;
use App\Model\Admin\Setting;
use App\Model\Entity\SettingEntity;
use Nette\Database\Table\ActiveRow;
use Nette\Mail\SendException;

readonly class SettingFacade
{
    public function __construct(
        private Setting $settingModel,
        private SenderFactory $senderFactory,
        private EncryptFacade $encryptFacade,
        private LogFacade $logFacade,
    ) {}

    /**
     * @param ?SettingEntity $setting
     */
    public function update(?ActiveRow $setting, \App\UI\Admin\Setting\Form\EditFormData $data): void
    {
        $updateData = (array) $data;
        if (null === $data->smtp_password) {
            unset($updateData['smtp_password']);
        } else {
            $updateData['smtp_password'] = $this->encryptFacade->encrypt($data->smtp_password);
        }

        if (null === $setting) {
            $setting = $this->settingModel->insert($updateData);
            $this->logFacade->create(LogActionEnum::Created, 'setting', $setting->id);
        } else {
            $setting->update($updateData);
            $this->logFacade->create(LogActionEnum::Updated, 'setting', $setting->id);
        }
    }

    /**
     * @throws SystemNameNotFoundException
     * @throws SendException
     */
    public function testEmail(\App\UI\Admin\Setting\Form\TestEmailFormData $data): void
    {
        $sender = $this->senderFactory->create(SystemNameEnum::TestEmail->value);
        $sender->addTo($data->email);
        $sender->addModifier('message', $data->message);
        $sender->send();
        $this->logFacade->create(LogActionEnum::TestEmail, 'setting');
    }

    public function updateAnalytics(ActiveRow|null $setting, Form\AnalyticsFormData $data):void
    {
        $updateData = (array) $data;
        if (null === $setting) {
            $setting = $this->settingModel->insert($updateData);
            $this->logFacade->create(LogActionEnum::Created, 'setting', $setting->id);
        } else {
            $setting->update($updateData);
            $this->logFacade->create(LogActionEnum::Updated, 'setting', $setting->id);
        }
    }
}
