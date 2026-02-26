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
use App\UI\Accessory\ParameterBag;
use Nette\Database\Table\ActiveRow;
use Nette\Mail\SendException;
use Nette\Utils\FileSystem;

class SettingFacade
{
    public const string DISCORD_ERROR_LOG_URL = 'discordErrorLogUrl';

    public function __construct(
        private readonly Setting       $settingModel,
        private readonly SenderFactory $senderFactory,
        private readonly EncryptFacade $encryptFacade,
        private readonly LogFacade     $logFacade,
        private readonly ParameterBag  $parameterBag,
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
            $discordUpdated = $setting->discord_error_log_url !== null;
        } else {
            $oldSetting = clone $setting;
            $setting->update($updateData);
            $this->logFacade->create(LogActionEnum::Updated, 'setting', $setting->id);
            $discordUpdated = $oldSetting->discord_error_log_url !== $setting->discord_error_log_url;
        }

        if($discordUpdated){
            if($setting->discord_error_log_url !== null){
                FileSystem::write($this->parameterBag->privateDir . '/' . self::DISCORD_ERROR_LOG_URL, $setting->discord_error_log_url);
            }else{
                FileSystem::delete($this->parameterBag->privateDir . '/' . self::DISCORD_ERROR_LOG_URL);
            }
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
