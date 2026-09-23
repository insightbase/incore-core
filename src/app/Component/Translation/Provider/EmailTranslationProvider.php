<?php

namespace App\Component\Translation\Provider;

use App\Component\Translation\TranslatedItemsProvider;
use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\Email;
use App\Model\Admin\EmailLanguage;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Zdroj překladu předmětu a textu e-mailových šablon.
 */
final readonly class EmailTranslationProvider implements TranslationProvider, TranslatedItemsProvider
{
    private const array FIELDS = ['subject', 'text'];

    public function __construct(
        private Email $emailModel,
        private EmailLanguage $emailLanguageModel,
    ) {}

    public function getSystemName(): string
    {
        return 'email';
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array
    {
        if ($id === null) {
            $emails = $this->emailModel->getTable();
        } else {
            $row = $this->emailModel->get($id);
            $emails = $row === null ? [] : [$row];
        }

        $items = [];
        foreach ($emails as $email) {
            foreach (self::FIELDS as $field) {
                if ($email->{$field} !== null && $email->{$field} !== '') {
                    $items[] = new TranslationItem($email->id, $field, $email->{$field});
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
        foreach ($this->emailLanguageModel->getTable()->where('language_id', $language->id) as $row) {
            foreach (self::FIELDS as $field) {
                if ($row->{$field} !== null && $row->{$field} !== '') {
                    $translated[$row->email_id][] = $field;
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
        if (!in_array($field, self::FIELDS, true) || is_array($value) || $this->emailModel->get($id) === null) {
            return;
        }

        $emailLanguage = $this->emailLanguageModel->getByEmailIdAndLanguage($id, $language);
        if ($emailLanguage === null) {
            $this->emailLanguageModel->insert([
                'email_id' => $id,
                'language_id' => $language->id,
                $field => $value,
            ]);
        } else {
            $emailLanguage->update([$field => $value]);
        }
    }
}
