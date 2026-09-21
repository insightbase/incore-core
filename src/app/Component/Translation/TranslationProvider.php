<?php

namespace App\Component\Translation;

use App\Model\Entity\LanguageEntity;
use Nette\Database\Table\ActiveRow;

/**
 * Zdroj textů pro automatický překlad. Třídy s tímto rozhraním se samy
 * zapojí do hromadného překladu spouštěného z administrace Jazyků.
 */
interface TranslationProvider
{
    /** Unikátní jméno zdroje ve tvaru ^[a-z][a-zA-Z0-9]*$, např. 'email'. */
    public function getSystemName(): string;

    /**
     * Texty k odeslání k překladu. `$id` omezí sběr na jedinou položku
     * (tlačítko „Přeložit“ u konkrétní entity). Zdroj z nenainstalovaného
     * modulu vrací prázdné pole.
     *
     * @param LanguageEntity $language cílový jazyk
     * @return TranslationItem[]
     */
    public function collect(ActiveRow $language, ?int $id = null): array;

    /**
     * Uloží přeloženou hodnotu.
     *
     * @param string|array<string, mixed> $value
     * @param LanguageEntity $language
     */
    public function save(int $id, string $field, string|array $value, ActiveRow $language): void;
}
