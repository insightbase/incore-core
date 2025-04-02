<?php

namespace App\Component\Log;

use App\Model\Admin\Log;
use Nette\Security\User;
use Nette\Utils\DateTime;

readonly class LogFacade
{
    public function __construct(
        private Log $logModel,
        private User $userSecurity,
    )
    {
    }

    public function create(LogActionEnum $actionEnum, string $table, ?int $id = null):void
    {
        $this->logModel->insert([
            'action' => $actionEnum->value,
            'created' => new DateTime(),
            'table' => $table,
            'target_id' => $id,
            'user_id' => $this->userSecurity->getId(),
        ]);
    }
}