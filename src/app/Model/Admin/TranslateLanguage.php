<?php

namespace App\Model\Admin;

use App\Model\Entity\LanguageEntity;
use App\Model\Entity\TranslateEntity;
use App\Model\Entity\TranslateLanguageEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class TranslateLanguage implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<TranslateLanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('translate_language');
    }

    /**
     * @param TranslateEntity $translate
     * @param LanguageEntity  $language
     *
     * @return ?TranslateLanguageEntity
     */
    public function getByTranslateAndLanguage(ActiveRow $translate, ActiveRow $language): ?ActiveRow
    {
        return $this->getByTranslateIdAndLanguageId($translate->id, $language->id);
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param LanguageEntity $language
     *
     * @return Selection<TranslateLanguageEntity>
     */
    public function getByLanguage(ActiveRow $language): Selection
    {
        return $this->getTable()->where('language_id', $language->id);
    }

    /**
     * @param int $translateId
     * @param int $languageId
     * @return ?TranslateLanguageEntity
     */
    public function getByTranslateIdAndLanguageId(int $translateId, int $languageId): ?ActiveRow
    {
        return $this->getTable()
            ->where('translate_id', $translateId)
            ->where('language_id', $languageId)
            ->fetch()
        ;
    }
}
