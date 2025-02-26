<?php

namespace App\Model\Admin;

use App\Model\Entity\LanguageEntity;
use App\Model\Model;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Language implements Model
{
    private Cache $cache;

    public function __construct(
        private Explorer $explorer,
        private Storage $storage,
    ) {
        $this->cache = new Cache($this->storage, 'language');
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('language');
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getToGrid(): Selection
    {
        return $this->getTable();
    }

    public function insert(array $data): void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @return ?LanguageEntity
     */
    public function get(int $id): ?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getToTranslate(): Selection
    {
        return $this->getTable()->where('active', true);
    }

    /**
     * @return Selection<LanguageEntity>
     */
    public function getToTranslateNotDefault(): Selection
    {
        return $this->getToTranslate()->where('is_default', false);
    }

    public function getExplorer(): Explorer
    {
        return $this->explorer;
    }

    public function getByUrl(string $url): ?ActiveRow
    {
        return $this->getTable()->where('url', $url)->fetch();
    }

    public function getDefault(): ?ActiveRow
    {
        return $this->getTable()->where('is_default', true)->fetch();
    }

    /**
     * @param string|null $host
     * @return ?LanguageEntity
     */
    public function getByHost(?string $host):?ActiveRow
    {
        return $this->getTable()->where('host', $host)->fetch();
    }
}
