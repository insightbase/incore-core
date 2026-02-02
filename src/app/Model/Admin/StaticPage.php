<?php

namespace App\Model\Admin;

use App\Model\Entity\SettingEntity;
use App\Model\Entity\StaticPageEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class StaticPage implements Model
{
    public function __construct(
        private Explorer $explorer,
    ) {}

    /**
     * @return Selection<StaticPageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('static_page');
    }

    /**
     * @param int $id
     * @return ?StaticPageEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @return Selection<StaticPageEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable();
    }

    /**
     * @param string $slug
     * @return ?StaticPageEntity
     */
    public function getBySlug(string $slug):?ActiveRow
    {
        return $this->getTable()
            ->where('active', true)
            ->where('slug', $slug)
            ->fetch();
    }

    /**
     * @param array $data
     * @return StaticPageEntity
     */
    public function insert(array $data):ActiveRow
    {
        return $this->getTable()->insert($data);
    }

    public function getToGridPerformance():Selection
    {
        return $this->getTable();
    }

    /**
     * @param string $systemName
     * @return ?StaticPageEntity
     */
    public function getBySystemName(string $systemName):?ActiveRow
    {
        return $this->getTable()->where('system_name', $systemName)->fetch();
    }
}