<?php

namespace App\Component\Translator;

class Translator implements \Nette\Localization\Translator
{

    /**
     * @param \Stringable|string $message
     * @param array<int|string|\Stringable> $parameters
     * @return string|\Stringable
     */
    function translate(\Stringable|string $message, ...$parameters): string|\Stringable
    {
        foreach($parameters as $key => $value){
            $message = str_replace("%$key%", (string)$value, $message);
        }
        return $message;
    }
}