<?php

namespace App\Component\Translation\Provider;

use App\Component\Translation\TranslatedItemsProvider;
use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\Language;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\LanguageEntity;
use App\Model\Enum\TranslateTypeEnum;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;

/**
 * Zdroj překladu slovníku UI textů (tabulky `translate` / `translate_language`).
 */
final readonly class TranslateTranslationProvider implements TranslationProvider, TranslatedItemsProvider
{
    private const array FIELDS = ['value'];

    public function __construct(
        private Translate $translateModel,
        private TranslateLanguage $translateLanguageModel,
        private Language $languageModel,
    ) {}

    public function getSystemName(): string
    {
        return 'translate';
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array
    {
        $defaultLanguage = $this->languageModel->getDefault();
        if ($defaultLanguage === null) {
            return [];
        }

        if ($id === null) {
            $translates = $this->translateModel->getNotAdmin();
        } else {
            $row = $this->translateModel->get($id);
            $translates = $row === null ? [] : [$row];
        }

        $items = [];
        foreach ($translates as $translate) {
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $defaultLanguage);
            if ($translateLanguage !== null) {
                $value = $translateLanguage->value;
                if ($translateLanguage->translate->type === TranslateTypeEnum::Html->value) {
                    $value = Json::decode($value, true);
                }
                $items[] = new TranslationItem($translate->id, 'value', $value);
            }
        }

        return $items;
    }

    /**
     * @param LanguageEntity $language
     * @return array<int, list<string>>
     */
    public function getTranslatedItems(ActiveRow $language): array
    {
        $translated = [];
        foreach ($this->translateLanguageModel->getTable()->where('language_id', $language->id) as $row) {
            foreach (self::FIELDS as $field) {
                if ($row->{$field} !== '') {
                    $translated[$row->translate_id][] = $field;
                }
            }
        }

        return $translated;
    }

    /**
     * @param string|array<string, mixed> $value
     * @param LanguageEntity $language
     */
    public function save(int $id, string $field, string|array $value, ActiveRow $language): void
    {
        if (!in_array($field, self::FIELDS, true)) {
            return;
        }

        $translate = $this->translateModel->get($id);
        if ($translate === null) {
            return;
        }

        if ($translate->type === TranslateTypeEnum::Html->value) {
            $value = Json::encode($value);
        }

        $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
        if ($translateLanguage === null) {
            $this->translateLanguageModel->insert([
                'value' => $value,
                'language_id' => $language->id,
                'translate_id' => $translate->id,
            ]);
        } else {
            $translateLanguage->update(['value' => $value]);
        }
    }
}
