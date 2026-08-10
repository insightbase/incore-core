<?php

namespace App\Model\Admin;

use App\Model\Entity\ImageLanguageEntity;
use App\Model\Entity\LanguageEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class ImageLanguage implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<ImageLanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('image_language');
    }

    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param LanguageEntity $language
     * @return ?ImageLanguageEntity
     */
    public function getByImageIdAndLanguage(int $imageId, ActiveRow $language): ?ActiveRow
    {
        return $this->getTable()
            ->where('image_id', $imageId)
            ->where('language_id', $language->id)
            ->fetch();
    }
}
