<?php

namespace App\Component\EditorJs;

interface EditorJsRendererFactory
{
    public function create():EditorJsRenderer;
}