<?php

namespace App\UI\Admin\LanguageTranslateLog;

use App\Model\Admin\LanguageTranslate;
use App\UI\Accessory\ParameterBag;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Finder;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class LanguageTranslateLogFacade
{
    /**
     * Název souboru s přijatým callbackem: language_callback_<timestamp>[_<pořadí>].
     * Soubory s příponou _error (neúspěšné zpracování) se úmyslně nezahrnují.
     */
    private const CallbackFileMask = 'language_callback_*';
    private const CallbackFilePattern = '~^language_callback_(\d+)(?:_\d+)?$~';

    public function __construct(
        private readonly LanguageTranslate $languageTranslateModel,
        private readonly ParameterBag $parameterBag,
    ) {}

    /**
     * Projde callbacky přijaté od DropCore a uložené v temp adresáři a označí
     * odpovídající záznamy logu jako dokončené. Vrací počet nově označených záznamů.
     */
    public function markFinishedByReceivedCallbacks(): int
    {
        $count = 0;
        foreach (Finder::findFiles(self::CallbackFileMask)->in($this->parameterBag->tempDir) as $file) {
            if (!preg_match(self::CallbackFilePattern, $file->getBasename(), $matches)) {
                continue;
            }

            $dropCoreId = $this->extractDropCoreId((string) FileSystem::read($file->getPathname()));
            if (null === $dropCoreId) {
                continue;
            }

            $count += $this->languageTranslateModel->markFinishedByDropCoreId(
                $dropCoreId,
                DateTime::from((int) $matches[1]),
            );
        }

        return $count;
    }

    /**
     * Vytáhne z těla callbacku DropCore ID požadavku. Vrací null, když tělo není
     * čitelné nebo ID neobsahuje.
     */
    private function extractDropCoreId(string $raw): ?string
    {
        try {
            $post = Json::decode($raw, true);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($post)) {
            return null;
        }

        // Stejná záchrana jako v LanguageCallbackPresenter: při „socket hang up“
        // je původní požadavek zabalený uvnitř chybové odpovědi.
        if (empty($post['valid'])
            && 'socket hang up' === ($post['error']['message'] ?? null)
            && isset($post['error']['config']['data'])
        ) {
            try {
                $post = Json::decode((string) $post['error']['config']['data'], true);
            } catch (JsonException) {
                return null;
            }
        }

        if (!is_array($post) || !isset($post['id']) || !is_scalar($post['id'])) {
            return null;
        }

        return (string) $post['id'];
    }
}
