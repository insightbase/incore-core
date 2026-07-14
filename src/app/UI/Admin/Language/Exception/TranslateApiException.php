<?php

namespace App\UI\Admin\Language\Exception;

class TranslateApiException extends \Exception
{
    public function __construct(
        string $message = '',
        private readonly int $sentChunks = 0,
        private readonly int $totalChunks = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Počet dávek úspěšně odeslaných na API před selháním.
     */
    public function getSentChunks(): int
    {
        return $this->sentChunks;
    }

    /**
     * Celkový počet dávek, které se měly odeslat.
     */
    public function getTotalChunks(): int
    {
        return $this->totalChunks;
    }

    /**
     * Selhalo odeslání až po tom, co část dávek už byla odeslána na API.
     */
    public function isPartial(): bool
    {
        return $this->sentChunks > 0 && $this->sentChunks < $this->totalChunks;
    }
}
