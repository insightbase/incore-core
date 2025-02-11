<?php

namespace App\UI\Translate;

use App\Component\Translator\Extractor\LatteExtractor;
use App\Component\Translator\Extraxtor\NetteTranslatorExtractor;
use App\Component\Translator\Translator;
use App\Model\Entity\TranslateEntity;
use App\Model\Language;
use App\Model\Module;
use App\Model\Translate;
use App\Model\TranslateLanguage;
use App\UI\Accessory\ParameterBag;
use App\UI\Translate\Form\FormTranslateData;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Finder;
use Symfony\Component\Translation\Extractor\ChainExtractor;
use Symfony\Component\Translation\MessageCatalogue;

readonly class TranslateFacade
{
    public function __construct(
        private TranslateLanguage $translateLanguageModel,
        private Language $languageModel,
        private LatteExtractor $latteExtractor,
        private NetteTranslatorExtractor $netteTranslatorExtractor,
        private ParameterBag $parameterBag,
        private Module $moduleModel,
        private Translate $translateModel,
        private Storage $storage,
    ) {}

    /**
     * @param TranslateEntity $translate
     */
    public function translate(ActiveRow $translate, FormTranslateData $data): void
    {
        $cache = new Cache($this->storage, Translator::CACHE_NAMESPACE);
        foreach ($this->languageModel->getToTranslate() as $language) {
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
            if ($translateLanguage) {
                if (null === $data->languageInput[$language->id]) {
                    $translateLanguage->delete();
                } else {
                    $translateLanguage->update(['value' => $data->languageInput[$language->id]]);
                }
            } else {
                if (null !== $data->languageInput[$language->id]) {
                    $this->translateLanguageModel->insert([
                        'value' => $data->languageInput[$language->id],
                        'translate_id' => $translate->id,
                        'language_id' => $language->id,
                    ]);
                }
            }
            $cache->remove($language->id);
        }
    }

    public function synchronize(): void
    {
        $extractor = new ChainExtractor();
        $extractor->addExtractor('latte', $this->latteExtractor);
        $extractor->addExtractor('php', $this->netteTranslatorExtractor);

        $vendorIncoreDir = $this->parameterBag->appDir.'/../vendor/incore/';

        $extractorKeys = [];

        foreach (Finder::findDirectories('*')->in($vendorIncoreDir) as $incoreModule) {
            $module = $this->moduleModel->getBySystemName($incoreModule->getBasename());
            if (null === $module) {
                $moduleName = 'core';
            } else {
                $moduleName = $module->system_name;
            }
            if (!array_key_exists($moduleName, $extractorKeys)) {
                $extractorKeys[$moduleName] = new MessageCatalogue('cs');
            }
            $extractor->extract($incoreModule->getPathname(), $extractorKeys[$moduleName]);
        }

        $this->updateTranslates($extractorKeys);
    }

    /**
     * @param array<string, MessageCatalogue> $extractorKeys
     */
    private function updateTranslates(array $extractorKeys): void
    {
        $dataInsert = [];

        $allKeys = [];

        foreach ($extractorKeys as $module => $keys) {
            foreach ($keys->getMetadata() as $key => $data) {
                if (!in_array($key, $allKeys)) {
                    $allKeys[] = $key;
                }
            }
        }

        foreach ($allKeys as $key) {
            $translate = $this->translateModel->getByKey($key);
            if (null === $translate) {
                $dataInsert[] = [
                    'key' => $key,
                ];
            }
        }

        if (!empty($dataInsert)) {
            $this->translateModel->insert($dataInsert);
        }
        $this->translateModel->getNotKeys($allKeys)->delete();
    }
}
