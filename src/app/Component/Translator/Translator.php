<?php

namespace App\Component\Translator;

use App\Model\Entity\LanguageEntity;
use App\Model\Language;
use App\Model\TranslateLanguage;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;

class Translator implements \Nette\Localization\Translator
{
    public const CACHE_NAMESPACE = 'translator';
    private Cache $cache;

    /**
     * @var array<string[]>
     */
    private array $messages = [];

    /**
     * @var LanguageEntity
     */
    private ActiveRow $language;

    public function __construct(
        private readonly Storage $storage,
        private readonly Language $languageModel,
        private readonly TranslateLanguage $translateLanguageModel,
    ) {
        $this->cache = new Cache($this->storage, self::CACHE_NAMESPACE);
    }

    /**
     * @param array<int|string|\Stringable> $parameters
     */
    public function translate(string|\Stringable $message, ...$parameters): string|\Stringable
    {
        $translated = $this->getTranslate($message);
        if (null === $translated) {
            $translated = $message;
        }

        foreach ($parameters as $key => $value) {
            $translated = str_replace("%{$key}%", (string) $value, $translated);
        }

        return $translated;
    }

    public function setLang(string $lang): void
    {
        $language = $this->languageModel->getByUrl($lang);
        if (null === $language) {
            $language = $this->languageModel->getDefault();
        }
        $this->language = $language;
    }

    private function getTranslate(string $message): ?string
    {
        if (!array_key_exists($this->language->id, $this->messages)) {
            $this->messages[$this->language->id] = $this->getMessages();
        }
        if (!array_key_exists($message, $this->messages[$this->language->id])) {
            return null;
        }

        return $this->messages[$this->language->id][$message];
    }

    private function getMessages(): array
    {
        return $this->cache->load($this->language->id, function (): array {
            $messages = [];
            foreach ($this->translateLanguageModel->getByLanguage($this->language) as $translateLanguage) {
                $messages[$translateLanguage->translate->key] = $translateLanguage->value;
            }

            return $messages;
        });
    }
}
