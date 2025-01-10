<?php

namespace App\Model;

use App\Model\Entity\LanguageEntity;
use App\Model\Entity\TranslateEntity;
use App\Model\Entity\TranslateLanguageEntity;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class TranslateLanguage implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<TranslateLanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('translate_language');
    }

    /**
     * @param TranslateEntity $translate
     * @param LanguageEntity $language
     * @return ?TranslateLanguageEntity
     */
    public function getByTranslateAndLanguage(ActiveRow $translate, ActiveRow $language): ?ActiveRow{
        return $this->getTable()
            ->where('translate_id', $translate->id)
            ->where('language_id', $language->id)
            ->fetch();
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }
}