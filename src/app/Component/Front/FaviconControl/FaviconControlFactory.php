<?php

namespace App\Component\Front\FaviconControl;

interface FaviconControlFactory
{
    public function create():FaviconControl;
}