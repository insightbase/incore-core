<?php

namespace App\Model\Enum;

enum PrivilegeEnum:string
{
    case Set = 'set';
    case Authorization = 'authorization';
    case Synchronize = 'synchronize';
    case Translate = 'translate';
    case Delete = 'delete';
    case New = 'new';
    case Edit = 'edit';
    case Default = 'default';
}
