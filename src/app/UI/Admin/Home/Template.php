<?php

namespace App\UI\Admin\Home;

use App\UI\Admin\BaseTemplate;
use App\UI\Admin\Home\GaGraph\GaGraphItem;

class Template extends BaseTemplate
{
    public bool $notConfigured;
    /**
     * @var GaGraphItem[]
     */
    public array $dataAccessGraph;
    /**
     * @var true
     */
    public bool $analyticsError = false;
}