<?php

namespace App\UI\Accessory\Admin\Form\Controls\Dropzone;

enum DropzoneImageLocationEnum:string
{
    case Favicon = 'favicon';
    case LanguageFlag = 'languageFlag';
    case UserAvatar = 'userAvatar';
    case SettingLogo = 'settingLogo';
    case SettingShareImage = 'settingShareImage';
    case Enumeration = 'enumeration';
    case Content = 'content';
    case ContentGallery = 'contentGallery';
    case Blog = 'blog';
}
