<?php

namespace App\Component\Translation;

/**
 * Výsledek odeslání textů do DropCore: kolik textů odešlo a v jakých dávkách
 * (ID dávek sleduje lišta s průběhem, viz TranslationJobFacade).
 */
final readonly class TranslationSendResult
{
    /**
     * @param list<string> $dropCoreIds
     */
    public function __construct(
        public int $itemCount,
        public array $dropCoreIds,
    ) {}
}
