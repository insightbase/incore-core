<?php

namespace App\Component\Translator\Extractor;

use App\Component\Translator\Extraxtor\PhpExtractor;
use App\Component\Translator\Translator;
use Latte\Engine;
use Nette\Application\UI\ITemplateFactory;
use Nette\Application\UI\TemplateFactory;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Utils\Finder;
use Symfony\Component\Translation\Extractor\ExtractorInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class LatteExtractor implements ExtractorInterface
{
    /** @var Engine */
    private Engine $latte;
    /** @var PhpExtractor */
    private PhpExtractor $compiledLatteExtractor;


    public function __construct(TemplateFactory $templateFactory, Translator $translator)
    {
        $template = $templateFactory->createTemplate();
        assert($template instanceof Template);
        $template->setTranslator($translator);
        $this->latte = $template->getLatte();

        $this->compiledLatteExtractor = new class () extends PhpExtractor {
            protected array $sequences = [
                [
                    '$this',
                    '->',
                    'filters',
                    '->',
                    'translate',
                    ')',
                    '(',
                    PhpExtractor::MESSAGE_TOKEN,
                ],
                [
                    'call_user_func',
                    '(',
                    '$this',
                    '->',
                    'filters',
                    '->',
                    'translate',
                    ',',
                    PhpExtractor::MESSAGE_TOKEN,
                ],
                [
                    '$this',
                    '->',
                    'filters',
                    '->',
                    'filterContent',
                    '(',
                    '"translate"',
                    ',',
                    '$_fi',
                    ',',
                    PhpExtractor::MESSAGE_TOKEN,
                ],
            ];
        };
    }


    public function extract(string|iterable $resource, MessageCatalogue $catalogue): void
    {
        if(is_string($resource)) {
            foreach (Finder::findFiles('*.latte')->from($resource) as $file) {
                $this->extractFile($file, $catalogue);
            }
        } else {
            foreach($resource as $res) {
                foreach (Finder::findFiles('*.latte')->from($res) as $file) {
                    $this->extractFile($file, $catalogue);
                }
            }
        }
    }


    private function extractFile(\SplFileInfo $file, MessageCatalogue $catalogue): void
    {
        $filePath = $file->getPathname();
        $compiledTemplateFile = $this->latte->getCacheFile($filePath);
        $this->latte->warmupCache($filePath);

        $this->compiledLatteExtractor->extract($compiledTemplateFile, $catalogue);
    }

    public function setPrefix(string $prefix): void
    {
    }
}
