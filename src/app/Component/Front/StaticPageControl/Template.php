<?php

namespace App\Component\Front\StaticPageControl;

use App\Component\Image\ImageControl;

class Template extends \Nette\Bridges\ApplicationLatte\Template
{
    public ImageControl $imageControl;
    public Dto\StaticPageDto $staticPageDto;
    public \App\Component\EditorJs\EditorJsRenderer $editorJsRenderer;

}