<?php

namespace App\Model\Admin;

use App\Model\Entity\EmailLanguageEntity;
use App\Model\Entity\LanguageEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class EmailLanguage implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<EmailLanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('email_language');
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
     * @return ?EmailLanguageEntity
     */
    public function getByEmailIdAndLanguage(int $emailId, ActiveRow $language): ?ActiveRow
    {
        return $this->getTable()
            ->where('email_id', $emailId)
            ->where('language_id', $language->id)
            ->fetch();
    }
}
