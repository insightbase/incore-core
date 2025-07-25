<?php

namespace App\Component\Translator\Extraxtor;

use App\Model\Enum\TranslateTypeEnum;
use Nette\Utils\FileSystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Translation\Extractor\AbstractFileExtractor;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

class PhpExtractor extends AbstractFileExtractor implements ExtractorInterface
{
    public const MESSAGE_TOKEN = 300;
    public const METHOD_ARGUMENTS_TOKEN = 1000;
    public const DOMAIN_TOKEN = 1001;

    /**
     * The sequence that captures translation messages.
     *
     * @var array<int, array<int, int|string>>
     */
    protected array $sequences = [
        [
            '->',
            'trans',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            '->',
            'trans',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            'new',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'new',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            'new',
            '\\',
            'Symfony',
            '\\',
            'Component',
            '\\',
            'Translation',
            '\\',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'new',
            '\Symfony\Component\Translation\TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            'new',
            '\\',
            'Symfony',
            '\\',
            'Component',
            '\\',
            'Translation',
            '\\',
            'TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            'new',
            '\Symfony\Component\Translation\TranslatableMessage',
            '(',
            self::MESSAGE_TOKEN,
        ],
        [
            't',
            '(',
            self::MESSAGE_TOKEN,
            ',',
            self::METHOD_ARGUMENTS_TOKEN,
            ',',
            self::DOMAIN_TOKEN,
        ],
        [
            't',
            '(',
            self::MESSAGE_TOKEN,
        ],
    ];

    /**
     * Prefix for new found message.
     */
    private string $prefix = '';

    public function extract($resource, MessageCatalogue $catalog): void
    {
        $files = $this->extractFiles($resource);
        foreach ($files as $file) {
            $this->parseTokens(token_get_all((string) file_get_contents($file)), $catalog, $file);

            gc_mem_caches();
        }
    }

    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Normalizes a token.
     *
     * @param mixed $token
     */
    protected function normalizeToken($token): ?string
    {
        if (isset($token[1]) && 'b"' !== $token) {
            return $token[1];
        }

        return $token;
    }

    /**
     * Extracts trans message from PHP tokens.
     *
     * @param array<int, array<int, int|string>|string> $tokens
     */
    protected function parseTokens(array $tokens, MessageCatalogue $catalog, string $filename): void
    {
        $tokenIterator = new \ArrayIterator($tokens);

        preg_match_all("/'([^']+)'(?:\s*,\s*type:\s*([A-Za-z0-9\\\\_\\:\\\\]+(?:\:[A-Za-z0-9\\\\_\\:\\\\]+)*))/", FileSystem::read($filename), $matches);

        for ($key = 0; $key < $tokenIterator->count(); ++$key) {
            foreach ($this->sequences as $sequence) {
                $message = '';
                $domain = 'messages';
                $tokenIterator->seek($key);

                foreach ($sequence as $sequenceKey => $item) {
                    $this->seekToNextRelevantToken($tokenIterator);

                    if ($this->normalizeToken($tokenIterator->current()) === $item) {
                        $tokenIterator->next();

                        continue;
                    }
                    if (self::MESSAGE_TOKEN === $item) {
                        $message = $this->getValue($tokenIterator);

                        if (\count($sequence) === ($sequenceKey + 1)) {
                            break;
                        }
                    } elseif (self::METHOD_ARGUMENTS_TOKEN === $item) {
                        $this->skipMethodArgument($tokenIterator);
                    } elseif (self::DOMAIN_TOKEN === $item) {
                        $domainToken = $this->getValue($tokenIterator);
                        if ('' !== $domainToken) {
                            $domain = $domainToken;
                        }

                        break;
                    } else {
                        break;
                    }
                }

                if ($message) {
                    $type = TranslateTypeEnum::Text;
                    for($i = 0; $i < count($matches[1]); $i++){
                        if($matches[1][$i] === $message){

                            preg_match('/::([A-Za-z0-9_]+)/', $matches[2][$i], $matchesEnum);

                            if (isset($matchesEnum[1])) {
                                $enumName = $matchesEnum[1];
                                foreach(TranslateTypeEnum::cases() as $value){
                                    if($value->name === $enumName){
                                        $type = $value;
                                        break;
                                    }
                                }
                            }
                        }
                    }

                    $catalog->set($message, $this->prefix.$message, $domain);
                    $metadata = $catalog->getMetadata($message, $domain) ?? [];
                    $normalizedFilename = preg_replace('{[\\\/]+}', '/', $filename);
                    $metadata['sources'][] = $normalizedFilename.':'.$tokens[$key][2];
                    $metadata['type'][] = $type;
                    $catalog->setMetadata($message, $metadata, $domain);

                    break;
                }
            }
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function canBeExtracted(string $file): bool
    {
        return $this->isFile($file) && 'php' === pathinfo($file, \PATHINFO_EXTENSION);
    }

    /**
     * @param array<string>|string $directory
     */
    protected function extractFromDirectory(array|string $directory): Finder
    {
        $finder = new Finder();

        return $finder->files()->name('*.php')->in($directory);
    }

    /**
     * Seeks to a non-whitespace token.
     */
    private function seekToNextRelevantToken(\Iterator $tokenIterator): void
    {
        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if (\T_WHITESPACE !== $t[0]) {
                break;
            }
        }
    }

    private function skipMethodArgument(\Iterator $tokenIterator): void
    {
        $openBraces = 0;

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();

            if ('[' === $t[0] || '(' === $t[0]) {
                ++$openBraces;
            }

            if (']' === $t[0] || ')' === $t[0]) {
                --$openBraces;
            }

            if ((0 === $openBraces && ',' === $t[0]) || (-1 === $openBraces && ')' === $t[0])) {
                break;
            }
        }
    }

    /**
     * Extracts the message from the iterator while the tokens
     * match allowed message tokens.
     */
    private function getValue(\Iterator $tokenIterator): string
    {
        $message = '';
        $docToken = '';
        $docPart = '';

        for (; $tokenIterator->valid(); $tokenIterator->next()) {
            $t = $tokenIterator->current();
            if ('.' === $t) {
                // Concatenate with next token
                continue;
            }
            if (!isset($t[1])) {
                break;
            }

            switch ($t[0]) {
                case \T_START_HEREDOC:
                    $docToken = $t[1];

                    break;

                case \T_ENCAPSED_AND_WHITESPACE:
                case \T_CONSTANT_ENCAPSED_STRING:
                    if ('' === $docToken) {
                        $message .= PhpStringTokenParser::parse($t[1]);
                    } else {
                        $docPart = $t[1];
                    }

                    break;

                case \T_END_HEREDOC:
                    $message .= PhpStringTokenParser::parseDocString($docToken, $docPart);
                    $docToken = '';
                    $docPart = '';

                    break;

                case \T_WHITESPACE:
                    break;

                default:
                    break 2;
            }
        }

        return $message;
    }
}
