<?php

namespace App\Model\Admin;

use App\Model\Entity\ImageEntity;
use App\Model\Entity\ImageLocationEntity;
use App\Model\Model;
use App\UI\Accessory\Admin\Form\Controls\Dropzone\DropzoneImageLocationEnum;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class ImageLocation implements Model
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
        return $this->explorer->table('image_location');
    }

    /**
     * @param DropzoneImageLocationEnum $locationEnum
     * @return ?ImageLocationEntity
     */
    public function getByLocation(DropzoneImageLocationEnum $locationEnum):?ActiveRow
    {
        return $this->getTable()->where('location', $locationEnum->value)->fetch();
    }
}