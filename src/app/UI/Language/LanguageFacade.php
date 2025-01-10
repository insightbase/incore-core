<?php

namespace App\UI\Language;

use App\Model\Language;
use App\UI\Language\Form\NewFormData;
use Nette\Database\Table\ActiveRow;

readonly class LanguageFacade
{
    public function __construct(
        private Language $language,
    )
    {
    }

    public function create(NewFormData $data):void
    {
        $this->language->insert((array)$data);
    }

    public function delete(ActiveRow $language):void
    {
        $language->delete();
    }

    public function update(ActiveRow $language, Form\EditFormData $data):void
    {
        $language->update((array)$data);
    }
}