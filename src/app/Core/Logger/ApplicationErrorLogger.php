<?php

declare(strict_types=1);

namespace App\Core\Logger;

use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Tracy\Debugger;
use Tracy\ILogger;

class ApplicationErrorLogger
{
    public function onError(Application $app, \Throwable $e): void
    {
        if (!$e instanceof BadRequestException) {
            Debugger::log($e, ILogger::EXCEPTION);
        }
    }
}
