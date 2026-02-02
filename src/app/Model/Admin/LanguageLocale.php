<?php

namespace App\Model\Admin;

use App\Model\Entity\ImageEntity;
use App\Model\Entity\LanguageEntity;
use App\Model\Entity\LanguageLocaleEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class LanguageLocale implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<LanguageLocaleEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('language_locale');
    }

    /**
     * @param LanguageEntity $language
     * @return Selection<LanguageLocaleEntity>
     */
    public function getByLanguage(ActiveRow $language):Selection
    {
        return $this->getTable()->where('language_id', $language->id);
    }

    /**
     * @param LanguageEntity $language
     * @param LanguageEntity $locale
     * @return ?LanguageLocaleEntity
     */
    public function getByLanguageAndLocale(ActiveRow $language, ActiveRow $locale):?ActiveRow{
        return $this->getTable()
            ->where('language_id', $language->id)
            ->where('locale_id', $locale->id)
            ->fetch();
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }
}