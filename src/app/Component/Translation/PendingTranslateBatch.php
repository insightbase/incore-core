<?php

namespace App\Component\Translation;

/**
 * Dávka z language_translate, kterou si odesílání zamklo a má ji poslat do DropCore.
 */
final readonly class PendingTranslateBatch
{
    public function __construct(
        public int $id,
        public int $languageId,
        public string $request,
        /** čas zamčení - jen request, který dávku zamkl, smí uložit její výsledek */
        public \DateTimeImmutable $claimedAt,
    ) {}
}
