<?php

namespace App\Component\Log;

use App\Component\Translator\Translator;

enum LogActionEnum:string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Imported = 'imported';
    case DeletedUnused = 'deletedUnused';
    case ChangeDefault = 'changeDefault';
    case ChangeActive = 'changeActive';
    case ChangePassword = 'changePassword';
    case SetAuthorization = 'setAuthorization';
    case TestEmail = 'testEmail';
    case Translate = 'translate';
    case Synchronize = 'synchronize';
    case CreatedItem = 'createdItem';
    case UpdatedItem = 'updatedItem';

    case DeletedItem = 'deletedItem';

    public function translate(Translator $translator):string
    {
        return match($this){
            self::Created => $translator->translate('action_created'),
            self::Updated => $translator->translate('action_updated'),
            self::Deleted => $translator->translate('action_deleted'),
            self::Imported => $translator->translate('action_imported'),
            self::DeletedUnused => $translator->translate('action_deletedUnused'),
            self::ChangeDefault => $translator->translate('action_changeDefault'),
            self::ChangeActive => $translator->translate('action_changeActive'),
            self::ChangePassword => $translator->translate('action_changePassword'),
            self::SetAuthorization => $translator->translate('action_setAuthorization'),
            self::TestEmail => $translator->translate('action_testedEmail'),
            self::Translate => $translator->translate('action_translated'),
            self::Synchronize => $translator->translate('action_synchronized'),
            self::CreatedItem => $translator->translate('action_createdItem'),
            self::UpdatedItem => $translator->translate('action_updatedItem'),
            self::DeletedItem => $translator->translate('action_deletedItem'),
        };
    }
}
