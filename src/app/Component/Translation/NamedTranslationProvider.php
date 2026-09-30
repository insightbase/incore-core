<?php

namespace App\Component\Translation;

/**
 * Volitelné rozšíření TranslationProvideru. Zdroj, který ho implementuje, umí
 * pojmenovat své položky - názvy se posílají v metadatech dávky do DropCore,
 * aby v jeho logu bylo vidět, co se překládalo („Články: Jak na to, Novinky“).
 * Zdroj bez tohoto rozhraní je v metadatech jen svým názvem, bez výčtu položek.
 */
interface NamedTranslationProvider
{
    /**
     * @param list<int> $ids TranslationItem::$id položek v dávce
     * @return array<int, string> id => název ve výchozím jazyce; neznámé id se vynechá
     */
    public function getItemNames(array $ids): array;
}
