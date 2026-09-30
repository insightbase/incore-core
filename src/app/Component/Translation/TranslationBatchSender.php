<?php

namespace App\Component\Translation;

use App\Component\DropCore\DropCoreEarlyCallbackStore;
use App\Component\DropCore\TranslateRequestSender;
use App\Model\Admin\LanguageTranslate;
use App\UI\Admin\Language\Exception\NotEnoughCreditsException;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Odesílá do DropCore uložené dávky úlohy z lišty s průběhem, vždy jednu za volání.
 */
final readonly class TranslationBatchSender
{
    public function __construct(
        private LanguageTranslate $languageTranslateModel,
        private TranslateRequestSender $translateRequestSender,
        private DropCoreEarlyCallbackStore $earlyCallbackStore,
    ) {}

    /**
     * Odešle další neodeslanou dávku. Vrací true, když nějakou dávku zpracoval (odeslal ji,
     * nebo u ní uložil chybu); false, když nebylo co odeslat, úloha stojí na chybě, nebo
     * dávku právě odesílá jiný request (druhá záložka).
     *
     * @param list<int> $translateIds řádky language_translate úlohy v pořadí dávek
     */
    public function sendNext(array $translateIds): bool
    {
        // Po chybě se čeká na „Zkusit znovu“ - další dávky by selhaly stejně (např. kredity).
        if ($this->languageTranslateModel->hasError($translateIds)) {
            return false;
        }

        $batch = $this->languageTranslateModel->claimNext($translateIds, new \DateTimeImmutable());
        if ($batch === null) {
            return false;
        }

        $batchIndex = (int) array_search($batch->id, $translateIds, true);
        try {
            $dropCoreId = $this->translateRequestSender->send($batch->request, $batch->languageId, $batchIndex, count($translateIds));
        } catch (NotEnoughCreditsException $e) {
            Debugger::log($e, ILogger::WARNING);
            $this->languageTranslateModel->markError($batch->id, LanguageTranslate::ERROR_NOT_ENOUGH_CREDITS);

            return true;
        } catch (\Throwable $e) {
            // I neočekávaná chyba musí dávku odemknout, jinak by ji lišta nešla zkusit znovu.
            Debugger::log($e, ILogger::EXCEPTION);
            $this->languageTranslateModel->markError($batch->id, LanguageTranslate::ERROR_API);

            return true;
        }

        $this->languageTranslateModel->markSent($batch->id, $dropCoreId, $this->earlyCallbackStore->take($dropCoreId));

        return true;
    }
}
