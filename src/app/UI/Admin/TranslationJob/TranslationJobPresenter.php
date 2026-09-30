<?php

namespace App\UI\Admin\TranslationJob;

use App\Component\DropCore\DropCoreSimulator;
use App\Component\Translation\TranslationJobFacade;
use App\UI\Accessory\Admin\PresenterTrait\RequireLoggedUserTrait;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Language\LanguageFacade;
use Nette\Application\UI\Presenter;

/**
 * JSON endpointy pro lištu s průběhem překladu (assets/admin/translationJobs.js) - stav,
 * odesílání dávek do DropCore, zkusit znovu a zrušit.
 * Úlohy patří přihlášenému uživateli, proto stačí přihlášení bez dalšího oprávnění.
 */
class TranslationJobPresenter extends Presenter
{
    use RequireLoggedUserTrait;

    public function __construct(
        private readonly TranslationJobFacade $translationJobFacade,
        private readonly DropCoreSimulator $dropCoreSimulator,
        private readonly LanguageFacade $languageFacade,
        private readonly ParameterBag $parameterBag,
    ) {
        parent::__construct();
    }

    public function actionStatus(): void
    {
        // Simulace DropCore (jen debug režim) nemá worker - dávky, kterým uplynula
        // prodleva, „vrátí“ právě tady, stejnou cestou jako skutečný callback.
        if ($this->parameterBag->debugMode) {
            $this->dropCoreSimulator->processDue(function (int $languageId, array $post): void {
                try {
                    $this->languageFacade->processDropCoreCallback($languageId, $post);
                } catch (\Throwable $e) {
                    \Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
                }
            });
        }

        $this->sendJson(['jobs' => $this->translationJobFacade->getStatuses()]);
    }

    /**
     * Odešle další dávku úlohy. `sent` = zda se nějaká dávka zpracovala; lišta podle něj
     * pokračuje hned další dávkou, nebo počká (dávku drží jiná záložka).
     */
    public function actionSend(string $id): void
    {
        $sent = $this->translationJobFacade->sendNext($id);
        $this->sendJson(['sent' => $sent, 'jobs' => $this->translationJobFacade->getStatuses()]);
    }

    public function actionRetry(string $id): void
    {
        $this->translationJobFacade->retry($id);
        $this->sendJson(['jobs' => $this->translationJobFacade->getStatuses()]);
    }

    public function actionCancel(string $id): void
    {
        $this->translationJobFacade->cancel($id);
        $this->sendJson(['jobs' => $this->translationJobFacade->getStatuses()]);
    }

    public function actionDismiss(string $id): void
    {
        $this->translationJobFacade->dismiss($id);
        $this->sendJson(['ok' => true]);
    }
}
