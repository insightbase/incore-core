<?php

namespace App\UI\Admin\Email;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Model\Admin\Email;
use App\Model\Entity\EmailEntity;

readonly class EmailFacade
{
    public function __construct(
        private LogFacade $logFacade,
        private Email $emailModel,
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
     * @return void
     */
    public function update(\Nette\Database\Table\ActiveRow $email, Form\EditFormData $data):void
    {
        $email->update((array)$data);
        $this->logFacade->create(LogActionEnum::Updated, 'email', $email->id);
    }

    public function create(Form\NewFormData $data):void
    {
        $email = $this->emailModel->insert((array)$data);
        $this->logFacade->create(LogActionEnum::Created, 'email', $email?->id);
    }
}