<?php

namespace App\UI\Error\Error4xx;

use Nette\Bridges\ApplicationLatte\Template;

class Error4xxTemplate extends Template
{
    public int $httpCode;
}
