<?php

namespace App\Component\Translator\Extraxtor;

final class NetteTranslatorExtractor extends PhpExtractor
{
    public function __construct()
    {
        $this->sequences[] = [
            '->',
            'translate',
            '(',
            PhpExtractor::MESSAGE_TOKEN,
        ];
    }
}
