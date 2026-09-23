<?php

namespace App\Component\Translation\Provider;

use App\Component\Translation\TranslatedItemsProvider;
use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\StaticPage;
use App\Model\Admin\StaticPageLanguage;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Json;

/**
 * Zdroj překladu statických stránek. Pole `content` je na rozdíl od ostatních
 * uloženo jako JSON (EditorJs), proto se v `collect()` dekóduje na pole
 * a v `save()` se zase zakóduje zpět na řetězec.
 */
final readonly class StaticPageTranslationProvider implements TranslationProvider, TranslatedItemsProvider
{
    private const array FIELDS = ['name', 'title', 'description', 'keywords', 'content'];

    public function __construct(
        private StaticPage $staticPageModel,
        private StaticPageLanguage $staticPageLanguageModel,
    ) {}

    public function getSystemName(): string
    {
        return 'staticPage';
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array
    {
        if ($id === null) {
            $staticPages = $this->staticPageModel->getTable();
        } else {
            $row = $this->staticPageModel->get($id);
            $staticPages = $row === null ? [] : [$row];
        }

        $items = [];
        foreach ($staticPages as $staticPage) {
            foreach (self::FIELDS as $field) {
                if ($field === 'content') {
                    if ($staticPage->content !== null && $staticPage->content !== '') {
                        $items[] = new TranslationItem($staticPage->id, $field, Json::decode($staticPage->content, true));
                    }

                    continue;
                }

                if ($staticPage->{$field} !== null && $staticPage->{$field} !== '') {
                    $items[] = new TranslationItem($staticPage->id, $field, $staticPage->{$field});
                }
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
        foreach ($this->staticPageLanguageModel->getTable()->where('language_id', $language->id) as $row) {
            foreach (self::FIELDS as $field) {
                if ($row->{$field} !== null && $row->{$field} !== '') {
                    $translated[$row->static_page_id][] = $field;
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
        if (!in_array($field, self::FIELDS, true) || $this->staticPageModel->get($id) === null) {
            return;
        }

        if ($field === 'content') {
            $value = Json::encode($value);
        } elseif (is_array($value)) {
            return;
        }

        $staticPageLanguage = $this->staticPageLanguageModel->getByStaticPageIdAndLanguage($id, $language);
        if ($staticPageLanguage === null) {
            $this->staticPageLanguageModel->insert([
                'static_page_id' => $id,
                'language_id' => $language->id,
                $field => $value,
            ]);
        } else {
            $staticPageLanguage->update([$field => $value]);
        }
    }
}
