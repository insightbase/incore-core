<?php

namespace App\Model\Admin;

use App\Model\Entity\LanguageSettingEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class LanguageSetting implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {
    }

    /**
     * @return Selection<LanguageSettingEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('language_setting');
    }

    /**
     * @return LanguageSettingEntity
     */
    public function getSetting():ActiveRow
    {
        return $this->getTable()->fetch();
    }
}