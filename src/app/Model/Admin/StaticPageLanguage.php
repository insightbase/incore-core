<?php

namespace App\Model\Admin;

use App\Model\Entity\LanguageEntity;
use App\Model\Entity\StaticPageEntity;
use App\Model\Entity\StaticPageLanguageEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class StaticPageLanguage implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<StaticPageLanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('static_page_language');
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param StaticPageEntity $staticPage
     * @param LanguageEntity $language
     * @return ?StaticPageLanguageEntity
     */
    public function getByStaticPageAndLanguage(\Nette\Database\Table\ActiveRow $staticPage, ActiveRow $language):?ActiveRow{
        return $this->getByStaticPageIdAndLanguage($staticPage->id, $language);
    }

    /**
     * @param int $staticPageId
     * @param LanguageEntity $language
     * @return ?StaticPageLanguageEntity
     */
    public function getByStaticPageIdAndLanguage(int $staticPageId, ActiveRow $language):?ActiveRow{
        return $this->getTable()
            ->where('language_id', $language->id)
            ->where('static_page_id', $staticPageId)
            ->fetch();
    }
}