<?php

namespace App\Component\Translation\Provider;

use App\Component\Translation\TranslationItem;
use App\Component\Translation\TranslationProvider;
use App\Model\Admin\Email;
use App\Model\Admin\EmailLanguage;
use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Zdroj překladu předmětu a textu e-mailových šablon.
 */
final readonly class EmailTranslationProvider implements TranslationProvider
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
        $emails = $id === null
            ? $this->emailModel->getTable()
            : array_filter([$this->emailModel->get($id)]);

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
