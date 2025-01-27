<?php

namespace App\UI\Language;

use App\Component\Translator\Translator;
use App\Model\Entity\LanguageEntity;
use App\Model\Language;
use App\UI\Language\DataGrid\Exception\DefaultLanguageCannotByDeactivateException;
use App\UI\Language\Form\NewFormData;
use Nette\Database\Table\ActiveRow;

readonly class LanguageFacade
{
    public function __construct(
        private Language $language,
        private Translator $translator,
    ) {}

    public function create(NewFormData $data): void
    {
        $this->language->insert((array) $data);
    }

    public function delete(ActiveRow $language): void
    {
        $language->delete();
    }

    public function update(ActiveRow $language, Form\EditFormData $data): void
    {
        $language->update((array) $data);
    }

    /**
     * @param LanguageEntity $language
     */
    public function changeDefault(ActiveRow $language): void
    {
        $this->language->getExplorer()->transaction(function () use ($language) {
            $this->language->getTable()->update(['is_default' => false]);
            $language->update(['is_default' => true]);
        });
    }

    /**
     * @param LanguageEntity $language
     *
     * @throws DefaultLanguageCannotByDeactivateException
     */
    public function changeActive(ActiveRow $language): void
    {
        if ($language->active && $language->is_default) {
            throw new DefaultLanguageCannotByDeactivateException($this->translator->translate('flash_default_language_cannot_be_deactivate'));
        }

        $language->update(['active' => !$language->active]);
    }
}
