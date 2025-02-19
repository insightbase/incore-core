<?php

namespace App\UI\Admin\Image\DataGrid;

use App\Component\Datagrid\Entity\BooleanColumnEntity;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\ImageColumnEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Image\ImageFacade;
use App\Component\Translator\Translator;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Html;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private ImageFacade $imageFacade,
    )
    {
    }

    public function create():DataGridEntity
    {
        $usedIds = $this->imageFacade->getUsedImages();

        $dataGridEntity = new DataGridEntity();
        $dataGridEntity
            ->addColumn((new ImageColumnEntity('id', $this->translator->translate('column_image'))))
            ->addColumn((new ColumnEntity('alt', $this->translator->translate('column_alt'))
                ->setEnableSearchGlobal()
            ))
            ->addColumn((new ColumnEntity('name', $this->translator->translate('column_name'))
                ->setEnableSearchGlobal()
            ))
            ->addColumn((new ColumnEntity('author', $this->translator->translate('column_author'))
                ->setEnableSearchGlobal()
            ))
            ->addColumn((new ColumnEntity('description', $this->translator->translate('column_description'))
                ->setEnableSearchGlobal()
                ->setTruncate(100)
            ))
            ->addColumn((new BooleanColumnEntity('used', $this->translator->translate('column_used'))
                ->setGetColumnCallback(function(ActiveRow $row) use ($usedIds):string{
                    if (array_key_exists($row['id'], $usedIds)) {
                        $class = 'ki-filled ki-check-squared text-success';
                    } else {
                        $class = 'ki-filled ki-cross-square text-danger';
                    }

                    return Html::el('i')->class($class);
                })
                ->disableSort()
            ))
        ;

        $dataGridEntity->addMenu((new MenuEntity($this->translator->translate('menu_edit'), 'edit')));

        return $dataGridEntity;
    }
}