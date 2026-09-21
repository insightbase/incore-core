<?php

namespace App\Component\Translation\Provider;

use App\Component\Image\ImageFacade;
use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\Image;
use App\Model\Admin\ImageLanguage;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Zdroj překladu ALT textu, jména a popisu obrázků.
 */
final readonly class ImageTranslationProvider implements TranslationProvider
{
    private const array FIELDS = ['alt', 'name', 'description'];

    public function __construct(
        private Image $imageModel,
        private ImageLanguage $imageLanguageModel,
        private ImageFacade $imageFacade,
    ) {}

    public function getSystemName(): string
    {
        return 'image';
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array
    {
        if ($id === null) {
            $images = $this->imageModel->getTable();
        } else {
            $row = $this->imageModel->get($id);
            $images = $row === null ? [] : [$row];
        }

        $items = [];
        foreach ($images as $image) {
            foreach (self::FIELDS as $field) {
                if ($image->{$field} !== null && $image->{$field} !== '') {
                    $items[] = new TranslationItem($image->id, $field, $image->{$field});
                }
            }
        }

        return $items;
    }

    /**
     * @param string|array<string, mixed> $value
     * @param LanguageEntity $language
     */
    public function save(int $id, string $field, string|array $value, ActiveRow $language): void
    {
        if (!in_array($field, self::FIELDS, true) || is_array($value) || $this->imageModel->get($id) === null) {
            return;
        }

        $imageLanguage = $this->imageLanguageModel->getByImageIdAndLanguage($id, $language);
        if ($imageLanguage === null) {
            $this->imageLanguageModel->insert([
                'image_id' => $id,
                'language_id' => $language->id,
                $field => $value,
            ]);
        } else {
            $imageLanguage->update([$field => $value]);
        }

        $this->imageFacade->clearCache($id);
    }
}
