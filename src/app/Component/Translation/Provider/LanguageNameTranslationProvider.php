<?php

namespace App\Component\Translation\Provider;

use App\Component\Translation\LabeledTranslationProvider;
use App\Component\Translation\TranslatedItemsProvider;
use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\Language;
use App\Model\Admin\LanguageLocale;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Zdroj překladu názvů jazyků.
 */
final readonly class LanguageNameTranslationProvider implements TranslationProvider, TranslatedItemsProvider, LabeledTranslationProvider
{
    private const array FIELDS = ['name'];

    public function __construct(
        private Language $languageModel,
        private LanguageLocale $languageLocaleModel,
    ) {}

    public function getSystemName(): string
    {
        return 'language';
    }

    public function getLabel(): string
    {
        return 'translationSource_language';
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array
    {
        if ($id === null) {
            $languages = $this->languageModel->getTable();
        } else {
            $row = $this->languageModel->get($id);
            $languages = $row === null ? [] : [$row];
        }

        $items = [];
        foreach ($languages as $row) {
            $items[] = new TranslationItem($row->id, 'name', $row->name);
        }

        return $items;
    }

    /**
     * Řádek `language_locale` nese název jazyka `language_id` v jazyce `locale_id`
     * (stejně jako ho ukládá formulář jazyka), cílový jazyk překladu je tedy `locale_id`.
     *
     * @param LanguageEntity $language
     * @return array<int, list<string>>
     */
    public function getTranslatedItems(ActiveRow $language): array
    {
        $translated = [];
        foreach ($this->languageLocaleModel->getTable()->where('locale_id', $language->id) as $row) {
            foreach (self::FIELDS as $field) {
                if ($row->{$field} !== '') {
                    $translated[$row->language_id][] = $field;
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
        if (!in_array($field, self::FIELDS, true) || is_array($value)) {
            return;
        }

        // $id je jazyk, jehož název se překládá; $language je jazyk, do kterého se překládá.
        // `language.name` je název ve výchozím jazyce, překlad do něj proto nepatří
        // ani u výchozího jazyka - ukládá se vždy do `language_locale`.
        $namedLanguage = $this->languageModel->get($id);
        if ($namedLanguage === null) {
            return;
        }

        $languageLocale = $this->languageLocaleModel->getByLanguageAndLocale($namedLanguage, $language);
        if ($languageLocale === null) {
            $this->languageLocaleModel->insert([
                'language_id' => $namedLanguage->id,
                'locale_id' => $language->id,
                'name' => $value,
            ]);
        } else {
            $languageLocale->update(['name' => $value]);
        }
    }
}
