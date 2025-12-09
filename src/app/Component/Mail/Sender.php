<?php

namespace App\Component\Mail;

use App\Component\EncryptFacade;
use App\Component\Image\ImageControlFactory;
use App\Component\Mail\Exception\SystemNameNotFoundException;
use App\Component\Translator\Translator;
use App\Model\Admin\Email;
use App\Model\Admin\EmailLog;
use App\Model\Admin\Language;
use App\Model\Admin\Setting;
use App\UI\Accessory\ParameterBag;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\TemplateFactory;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Mail\SendmailMailer;
use Nette\Mail\SmtpMailer;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;

class Sender
{
    private Message $message;

    /**
     * @var array<string, string>
     */
    private array $modifier = [];

    /**
     * @var string[]
     */
    private array $address = [];

    public function __construct(
        private readonly string              $systemName,
        private readonly Email               $emailModel,
        private readonly EmailLog            $emailLogModel,
        private readonly ParameterBag        $parameterBag,
        private readonly Setting             $settingModel,
        private readonly EncryptFacade       $encryptFacade,
        private readonly LinkGenerator       $linkGenerator,
        private readonly TemplateFactory     $templateFactory,
        private readonly Translator          $translator,
        private readonly ImageControlFactory $imageControlFactory,
    ) {
        $this->message = new Message();
    }

    public function addAttachment(string $file): self
    {
        $this->message->addAttachment($file);

        return $this;
    }

    public function addModifier(string $modifier, string $text): self
    {
        $this->modifier[$modifier] = $text;

        return $this;
    }

    public function addTo(string $email): self
    {
        $this->message->addTo($email);
        $this->address[] = $email;

        return $this;
    }

    private function getMailer():Mailer{
        $setting = $this->settingModel->getDefault();
        if (null === $setting || null === $setting->smtp_host || null === $setting->smtp_username || null === $setting->smtp_password) {
            return new SendmailMailer();
        }

        return new SmtpMailer(
            host: $setting->smtp_host,
            username: $setting->smtp_username,
            password: $this->encryptFacade->encrypt($setting->smtp_password),
            encryption: 'ssl',
        );
    }

    /**
     * @throws SendException|SystemNameNotFoundException
     */
    public function send(): void
    {
        $email = $this->emailModel->getBySystemName($this->systemName);
        if (null === $email) {
            throw new SystemNameNotFoundException()->setSystemName($this->systemName);
        }

        $template = $this->templateFactory->createTemplate();
        $template->getLatte()->addProvider('uiControl', $this->linkGenerator);
        $template->setTranslator($this->translator);
        $template->setting = $this->settingModel->getDefault();
        $template->email = $email;
        $template->imageControl = $this->imageControlFactory->create();
        $template->linkGenerator = $this->linkGenerator;
        if($email->template !== null){
            $text = $template->renderToString($this->parameterBag->rootDir . '/' . $email->template);
        }else{
            $text = $template->renderToString(dirname(__FILE__) . '/template.latte');
        }

        foreach ($this->modifier as $modifier => $value) {
            $text = str_replace('%'.$modifier.'%', $value, $text);
        }

        if($this->settingModel->getDefault()?->email_sender !== null) {
            $this->message->setFrom($this->settingModel->getDefault()->email_sender);
        }
        $this->message->setSubject($email->subject);
        $this->message->setHtmlBody($text);

        try {
            $this->getMailer()->send($this->message);
            $this->log();
        } catch (SendException $e) {
            $this->log($e->getMessage());
            if (!$this->parameterBag->debugMode) {
                throw $e;
            }
        }
    }

    private function log(?string $error = null): void
    {
        foreach ($this->address as $address) {
            $this->emailLogModel->getTable()->insert([
                'subject' => $this->message->getSubject(),
                'created' => new DateTime(),
                'text' => $this->message->getHtmlBody(),
                'address' => $address,
                'error' => $error,
                'from' => $this->message->getFrom() === null ? null : Arrays::firstKey($this->message->getFrom()),
            ]);
        }
    }
}
