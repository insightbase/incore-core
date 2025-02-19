<?php

namespace App\UI\Admin\Error\Error4xx;

use Nette\Bridges\ApplicationLatte\Template;

class Error4xxTemplate extends Template
{
    public int $httpCode;
}
