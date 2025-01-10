<?php

namespace App\Component\Translator\Extraxtor;

class PhpStringTokenParser
{
    /**
     * @var string[]
     */
    protected static array $replacements = [
        '\\' => '\\',
        '$' => '$',
        'n' => "\n",
        'r' => "\r",
        't' => "\t",
        'f' => "\f",
        'v' => "\v",
        'e' => "\x1B",
    ];

    /**
     * Parses a string token.
     *
     * @param string $str String token content
     *
     * @return string The parsed string
     */
    public static function parse(string $str)
    {
        $bLength = 0;
        if ('b' === $str[0]) {
            $bLength = 1;
        }

        if ('\'' === $str[$bLength]) {
            return str_replace(
                ['\\\\', '\\\''],
                ['\\', '\''],
                substr($str, $bLength + 1, -1)
            );
        } else {
            return self::parseEscapeSequences(substr($str, $bLength + 1, -1), '"');
        }
    }

    /**
     * Parses escape sequences in strings (all string types apart from single quoted).
     *
     * @param string      $str   String without quotes
     * @param string|null $quote Quote type
     *
     * @return string String with escape sequences parsed
     */
    public static function parseEscapeSequences(string $str, string $quote = null)
    {
        if (null !== $quote) {
            $str = str_replace('\\'.$quote, $quote, $str);
        }

        return preg_replace_callback(
            '~\\\\([\\\\$nrtfve]|[xX][0-9a-fA-F]{1,2}|[0-7]{1,3})~',
            [__CLASS__, 'parseCallback'],
            $str
        );
    }

    /**
     * @param string[] $matches
     * @return string
     */
    private static function parseCallback(array $matches): string
    {
        $str = $matches[1];

        if (isset(self::$replacements[$str])) {
            return self::$replacements[$str];
        } elseif ('x' === $str[0] || 'X' === $str[0]) {
            return \chr((int)hexdec($str));
        } else {
            return \chr((int)octdec($str));
        }
    }

    /**
     * Parses a constant doc string.
     *
     * @param string $startToken Doc string start token content (<<<SMTHG)
     * @param string $str        String token content
     *
     * @return string Parsed string
     */
    public static function parseDocString(string $startToken, string $str)
    {
        // strip last newline (thanks tokenizer for sticking it into the string!)
        $str = preg_replace('~(\r\n|\n|\r)$~', '', $str);

        // nowdoc string
        if (false !== strpos($startToken, '\'')) {
            return $str;
        }

        return self::parseEscapeSequences($str, null);
    }
}
