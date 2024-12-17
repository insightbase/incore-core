<?php

namespace App\Component\Mail;

use App\Component\Mail\Exception\SystemNameNotFoundException;
use App\Model\Email;
use App\Model\EmailLog;
use App\UI\Accessory\ParameterBag;
use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\Mail\SendException;
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
        private readonly string $systemName,
        private readonly Email  $emailModel,
        private readonly Mailer $mailer,
        private readonly EmailLog $emailLogModel,
        private readonly ParameterBag $parameterBag
    )
    {
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

    /**
     * @return void
     * @throws SystemNameNotFoundException|SendException
     */
    public function send():void
    {
        $email = $this->emailModel->getBySystemName($this->systemName);
        if($email === null){
            throw (new SystemNameNotFoundException())->setSystemName($this->systemName);
        }

        $text = $email['text'];
        foreach($this->modifier as $modifier => $value) {
            $text = str_replace('%' . $modifier . '%', $value, $text);
        }

        $this->message->setSubject($email['subject']);
        $this->message->setHtmlBody($text);

        try {
            $this->mailer->send($this->message);
            $this->log();
        } catch(SendException $e) {
            $this->log($e->getMessage());
            if(!$this->parameterBag->debugMode) {
                throw $e;
            }
        }
    }

    private function log(?string $error = null): void
    {
        foreach($this->address as $address) {
            $this->emailLogModel->getTable()->insert([
                'subject' => $this->message->getSubject(),
                'created' => new DateTime(),
                'text' => $this->message->getHtmlBody(),
                'address' => $address,
                'error' => $error,
            ]);
        }
    }
}