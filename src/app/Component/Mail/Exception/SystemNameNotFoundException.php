<?php

namespace App\Component\Mail\Exception;

class SystemNameNotFoundException extends \Exception
{
    private string $systemName;

    public function getSystemName(): string
    {
        return $this->systemName;
    }

    public function setSystemName(string $systemName): self
    {
        $this->systemName = $systemName;

        return $this;
    }
}
