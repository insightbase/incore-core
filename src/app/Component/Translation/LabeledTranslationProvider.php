<?php

namespace App\Component\Translation;

/**
 * Volitelné rozšíření TranslationProvideru. Zdroj, který ho implementuje, má
 * čitelný název - zobrazí se v liště s průběhem překladu („Překlad: Tagy“).
 * Zdroj bez tohoto rozhraní se v liště označí svým systemName.
 */
interface LabeledTranslationProvider
{
    /**
     * Název zdroje pro uživatele. Může to být překladový klíč (projde přes
     * Translator), nebo rovnou text.
     */
    public function getLabel(): string;
}
