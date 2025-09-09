<?php

namespace App\Model\Admin;

use App\Model\Entity\FormHelpEntity;
use App\Model\Entity\FormHelpLanguageEntity;
use App\Model\Entity\LanguageEntity;
use App\Model\Model;
use Google\Auth\AccessToken;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;

readonly class FormHelpLanguage implements Model
{
    public function __construct(
        private Explorer $explorer,
    )
    {
    }

    /**
     * @return Selection<FormHelpLanguageEntity>
     */
    public function getTable(): Selection
    {
        return $this->explorer->table('form_help_language');
    }

    public function insert(array $data):void
    {
        $this->getTable()->insert($data);
    }

    /**
     * @param FormHelpEntity $formHelp
     * @param LanguageEntity $language
     * @return ?FormHelpLanguageEntity
     */
    public function getByFormHelpAndLanguage(\Nette\Database\Table\ActiveRow $formHelp, ActiveRow $language):?ActiveRow{
        return $this->getTable()
            ->where('language_id', $language->id)
            ->where('form_help_id', $formHelp->id)
            ->fetch();
    }

    /**
     * @param LanguageEntity $language
     * @return Selection
     */
    public function getByLanguage(ActiveRow $language):Selection{
        return $this->getTable()
            ->where('language_id', $language->id);
    }

    /**
     * @return Selection<FormHelpLanguageEntity>
     */
    public function getAll():Selection
    {
        return $this->getTable();
    }

    /**
     * @param FormHelpEntity $formHelp
     * @return Selection<FormHelpLanguageEntity>
     */
    public function getByFormHelp(ActiveRow $formHelp):Selection{
        return $this->getTable()->where('form_help_id', $formHelp->id);
    }
}