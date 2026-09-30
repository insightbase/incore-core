<?php

namespace App\Component\DropCore;

use App\UI\Admin\Language\Exception\NotEnoughCreditsException;
use App\UI\Admin\Language\Exception\TranslateApiException;

/**
 * Odešle jednu uloženou dávku překladu do DropCore.
 */
interface TranslateRequestSender
{
    /**
     * @param string $request JSON tělo požadavku /gen/translate z language_translate.request
     * @param int $batchIndex pořadí dávky v úloze (od 0)
     * @return string ID úlohy, které přidělil DropCore
     * @throws NotEnoughCreditsException
     * @throws TranslateApiException
     */
    public function send(string $request, int $languageId, int $batchIndex, int $totalBatches): string;
}
