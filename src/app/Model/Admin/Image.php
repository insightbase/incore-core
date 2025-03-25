<?php

namespace App\Model\Admin;

use App\Model\Entity\ImageEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class Image implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<ImageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('image');
    }

    /**
     * @param int $id
     * @return ?ImageEntity
     */
    public function get(int $id):?ActiveRow
    {
        return $this->getTable()->get($id);
    }

    /**
     * @param array $data
     * @return ImageEntity
     */
    public function insert(array $data):ActiveRow
    {
        return $this->getTable()->insert($data);
    }

    /**
     * @return Selection<ImageEntity>
     */
    public function getToGrid():Selection
    {
        return $this->getTable()->where('image_location.show_in_grid', true);
    }

    /**
     * @return Selection<ImageEntity>
     */
    public function getByIds(array $ids): Selection
    {
        return $this->getTable()->where('id IN (?)', $ids);
    }

    /**
     * @param string $originalName
     * @return ?ImageEntity
     */
    public function getByOriginalName(string $originalName):?ActiveRow
    {
        return $this->getTable()->where('original_name', $originalName)->fetch();
    }
}