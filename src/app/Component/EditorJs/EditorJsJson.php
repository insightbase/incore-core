<?php

namespace App\Component\EditorJs;

use Nette\Utils\Json;
use Nette\Utils\JsonException;

final class EditorJsJson
{
    /**
     * Prázdná hodnota pole EditorJs. Editor při odeslání formuláře zapíše i prázdný
     * obsah jako JSON (`{"time":…,"blocks":[],"version":…}`), takže samotné `!== ''`
     * nestačí. Hodnota, která není JSON s `blocks`, se posuzuje jako obyčejný text.
     */
    public static function isEmpty(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return true;
        }

        try {
            $data = Json::decode($value, true);
        } catch (JsonException) {
            return false;
        }

        return is_array($data) && array_key_exists('blocks', $data) && $data['blocks'] === [];
    }
}
