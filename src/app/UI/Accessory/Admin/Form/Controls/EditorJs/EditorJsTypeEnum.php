<?php

namespace App\UI\Accessory\Admin\Form\Controls\EditorJs;

enum EditorJsTypeEnum:string
{
    case Raw = 'raw';
    case List = 'list';
    case Header = 'header';
    case Table = 'table';
    case Faq = 'faq';
    case Citation = 'citation';
    case Spotify = 'spotify';
    case YouTube = 'youtube';
    case Audio = 'audio';
    case Gallery = 'gallery';
    case MultiImage = 'multiImage';

}
