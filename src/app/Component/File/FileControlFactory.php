<?php

namespace App\Component\File;

interface FileControlFactory
{
    public function create():FileControl;
}