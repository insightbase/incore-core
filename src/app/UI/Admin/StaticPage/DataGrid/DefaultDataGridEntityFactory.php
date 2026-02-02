<?php

namespace App\UI\Admin\StaticPage\DataGrid;

use App\Component\Datagrid\Entity\BooleanColumnEntity;
use App\Component\Datagrid\Entity\ColumnEntity;
use App\Component\Datagrid\Entity\DataGridEntity;
use App\Component\Datagrid\Entity\DeleteMenuEntity;
use App\Component\Datagrid\Entity\MenuEntity;
use App\Component\Datagrid\SortDirEnum;
use App\Component\Translator\Translator;
use App\Model\Admin\StaticPage;
use App\UI\Admin\StaticPage\StaticPageFacade;

readonly class DefaultDataGridEntityFactory
{
    public function __construct(
        private Translator $translator,
        private StaticPage $staticPageModel,
        private StaticPageFacade $staticPageFacade,
    )
    {
    }

    public function create():DataGridEntity
    {
        $dataGridEntity = new DataGridEntity();

        $dataGridEntity->setHeaderTitle($this->translator->translate('staticPage_listTitle'));
        $dataGridEntity->setDefaultOrder('name');
        $dataGridEntity->setDefaultOrderDir(SortDirEnum::ASC);

        $dataGridEntity->addColumn(new ColumnEntity('name', $this->translator->translate('column_staticPage_name'))
            ->setEnableSearchGlobal()
            ->setLink('edit')
        );

        $dataGridEntity->addColumn(new BooleanColumnEntity('active', $this->translator->translate('column_article_active'))
            ->setOnClickCallback(function(int $id):void{
                $staticPage = $this->staticPageModel->get($id);
                if($staticPage !== null){
                    $this->staticPageFacade->changeActive($staticPage, !$staticPage->active);
                }
            })
            ->setClassHeader(null)
        );

        $dataGridEntity->addMenu(new MenuEntity($this->translator->translate('menu_edit'), 'edit'));
        $dataGridEntity->addMenu(new DeleteMenuEntity($this->translator->translate('menu_delete'), 'delete'));

        return  $dataGridEntity;
    }
}