<?php

namespace App\Component\Translation;

/**
 * Výsledek založení překladu: kolik textů se uložilo k odeslání a v jakých dávkách
 * (ID řádků language_translate; odesílá je lišta s průběhem, viz TranslationJobFacade).
 */
final readonly class TranslationSendResult
{
    /**
     * @param list<int> $translateIds
     */
    public function __construct(
        public int $itemCount,
        public array $translateIds,
    ) {}
}
