<?php

namespace App\Component\Image;

interface ImageControlFactory
{
    public function create():ImageControl;
}