<?php

namespace App\Component\Front\FaviconControl;

class Template extends \Nette\Bridges\ApplicationLatte\Template
{
    /**
     * @var array<FaviconDto>
     */
    public array $favicons;
    public FaviconControl $control;
}