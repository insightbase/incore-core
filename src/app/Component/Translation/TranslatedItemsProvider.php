<?php

namespace App\Component\Translation;

use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Volitelné rozšíření TranslationProvideru. Zdroj, který ho implementuje,
 * umí říct, které své položky už v cílovém jazyce přeložené má — hromadný
 * překlad jazyka je pak neposílá znovu. Zdroj bez tohoto rozhraní posílá
 * při hromadném překladu vždy všechno.
 */
interface TranslatedItemsProvider
{
    /**
     * Položky, které už mají v jazyce `$language` překlad. Volá se až po
     * `collect()`, takže může počítat i s řádky, které `collect()` založil.
     *
     * @param LanguageEntity $language cílový jazyk
     * @return array<int, list<string>> id položky => přeložená pole
     */
    public function getTranslatedItems(ActiveRow $language): array;
}
