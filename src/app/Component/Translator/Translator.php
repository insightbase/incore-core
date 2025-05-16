<?php

namespace App\Component\Translator;

use App\Component\EditorJs\EditorJsRendererFactory;
use App\Model\Admin\Language;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\LanguageEntity;
use App\Model\Enum\TranslateTypeEnum;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class Translator implements \Nette\Localization\Translator
{
    public const string CACHE_NAMESPACE = 'translator';
    private Cache $cache;

    /**
     * @var array<string[]>
     */
    private array $messages = [];

    /**
     * @var LanguageEntity
     */
    private ActiveRow $language;
    private \App\Component\EditorJs\EditorJsRenderer $editorJsRenderer;

    public function __construct(
        private readonly Storage                 $storage,
        private readonly Language                $languageModel,
        private readonly TranslateLanguage       $translateLanguageModel,
        private readonly EditorJsRendererFactory $editorJsRendererFactory,
    ) {
        $this->cache = new Cache($this->storage, self::CACHE_NAMESPACE);
        $this->editorJsRenderer = $this->editorJsRendererFactory->create();
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

        $params = [];
        foreach($parameters as $key => $value){
            if(is_array($value)){
                $params += $value;
            }else{
                $params[$key] = $value;
            }
        }

        foreach ($params as $key => $value) {
            if($value instanceof TranslateTypeEnum){
                try {
                    $json = Json::decode((string)$translated, true);
                    if(is_array($json) && array_key_exists('time', $json)){
                        $translated = (string)$this->editorJsRenderer->render($translated);
                    }
                } catch (JsonException $e) {
                }
            }else {
                $translated = str_replace("%{$key}%", (string)$value, $translated);
            }
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
            $this->messages[$this->language->id] = $this->getMessages($this->language);
        }
        if (!array_key_exists($message, $this->messages[$this->language->id])) {
            $defaultLanguage = $this->languageModel->getDefault();
            if ($defaultLanguage !== null && !array_key_exists($defaultLanguage->id, $this->messages)) {
                $this->messages[$defaultLanguage->id] = $this->getMessages($defaultLanguage);
            }

            if (!array_key_exists($message, $this->messages[$defaultLanguage->id])) {
                return null;
            }else{
                return $this->messages[$defaultLanguage->id][$message];
            }
        }

        return $this->messages[$this->language->id][$message];
    }

    /**
     * @param LanguageEntity $language
     * @return array
     * @throws \Throwable
     */
    private function getMessages(ActiveRow $language): array
    {
        return $this->cache->load($language->id, function () use ($language): array {
            $messages = [];
            foreach ($this->translateLanguageModel->getByLanguage($language) as $translateLanguage) {
                $messages[$translateLanguage->translate->key] = $translateLanguage->value;
            }

            return $messages;
        });
    }

    /**
     * @return LanguageEntity
     */
    public function getLanguage():ActiveRow
    {
        return $this->language;
    }
}
