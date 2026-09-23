<?php

namespace App\Component\Translation\Provider;

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
final readonly class LanguageNameTranslationProvider implements TranslationProvider, TranslatedItemsProvider
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
     * @param LanguageEntity $language
     * @return array<int, list<string>>
     */
    public function getTranslatedItems(ActiveRow $language): array
    {
        $translated = [];
        foreach ($this->languageLocaleModel->getTable()->where('language_id', $language->id) as $row) {
            foreach (self::FIELDS as $field) {
                if ($row->{$field} !== '') {
                    $translated[$row->locale_id][] = $field;
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

        $locale = $this->languageModel->get($id);
        if ($locale === null) {
            return;
        }

        if ($locale->is_default) {
            $locale->update(['name' => $value]);
        } else {
            $languageLocale = $this->languageLocaleModel->getByLanguageAndLocale($language, $locale);
            if ($languageLocale === null) {
                $this->languageLocaleModel->insert([
                    'language_id' => $language->id,
                    'locale_id' => $locale->id,
                    'name' => $value,
                ]);
            } else {
                $languageLocale->update(['name' => $value]);
            }
        }
    }
}
