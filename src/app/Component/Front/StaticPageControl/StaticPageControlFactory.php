<?php

namespace App\Component\Front\StaticPageControl;

interface StaticPageControlFactory
{
    public function create():StaticPageControl;
}