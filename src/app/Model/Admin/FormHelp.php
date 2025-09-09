<?php

namespace App\Model\Admin;

use App\Model\Entity\FormHelpEntity;
use App\Model\Model;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class FormHelp implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<FormHelpEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('form_help');
    }

    /**
     * @param string|null $presenter
     * @param string $inputHtmlId
     * @return ?FormHelpEntity
     */
    public function getByPresenterAndInputHtmlId(?string $presenter, string $inputHtmlId):?ActiveRow
    {
        return $this->getTable()
            ->where('presenter', $presenter)
            ->where('input', $inputHtmlId)
            ->fetch();
    }

    /**
     * @param array $data
     * @return FormHelpEntity
     */
    public function insert(array $data):ActiveRow
    {
        return $this->getTable()->insert($data);
    }

    /**
     * @return Selection<FormHelpEntity>
     */
    public function getAll():Selection
    {
        return $this->getTable();
    }
}