<?php

namespace App\UI\Admin\Translate;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Component\Translator\Extractor\LatteExtractor;
use App\Component\Translator\Extraxtor\NetteTranslatorExtractor;
use App\Component\Translator\Translator;
use App\Model\Admin\Language;
use App\Model\Admin\Module;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\TranslateEntity;
use App\UI\Accessory\ParameterBag;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\Arrays;
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
        private LogFacade $logFacade,
    ) {}

    /**
     * @param TranslateEntity $translate
     */
    public function translate(ActiveRow $translate, array $data): void
    {
        $translate->update(['is_performance' => $data['is_performance']]);

        $cache = new Cache($this->storage, Translator::CACHE_NAMESPACE);
        foreach ($this->languageModel->getToTranslate() as $language) {
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
            if ($translateLanguage) {
                if (null === $data[$language->id]) {
                    $translateLanguage->delete();
                } else {
                    $translateLanguage->update(['value' => $data[$language->id]]);
                }
            } else {
                if (null !== $data[$language->id]) {
                    $this->translateLanguageModel->insert([
                        'value' => $data[$language->id],
                        'translate_id' => $translate->id,
                        'language_id' => $language->id,
                    ]);
                }
            }
            $cache->remove($language->id);
        }
        $this->logFacade->create(LogActionEnum::Translate, 'translate', $translate->id);
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
        foreach (Finder::findDirectories('*')->in($this->parameterBag->appDir) as $incoreModule) {
            if(str_ends_with($incoreModule->getPathname(), 'app/UI')) {
                $moduleName = 'admin';
                $module = $this->moduleModel->getBySystemName($incoreModule->getBasename());
                if (!array_key_exists($moduleName, $extractorKeys)) {
                    $extractorKeys[$moduleName] = new MessageCatalogue('cs');
                }
                $extractor->extract($incoreModule->getPathname() . '/Admin', $extractorKeys[$moduleName]);
            }
        }
        $this->updateTranslates($extractorKeys, 'admin');


        $extractorKeys = [];

        foreach (Finder::findDirectories('*')->in($this->parameterBag->appDir) as $incoreModule) {
            if(str_ends_with($incoreModule->getPathname(), 'app/UI')) {
                $moduleName = 'front';
                $module = $this->moduleModel->getBySystemName($incoreModule->getBasename());
                if (!array_key_exists($moduleName, $extractorKeys)) {
                    $extractorKeys[$moduleName] = new MessageCatalogue('cs');
                }
                $extractor->extract($incoreModule->getPathname() . '/Front', $extractorKeys[$moduleName]);
            }else{
                $moduleName = 'front';
                $module = $this->moduleModel->getBySystemName($incoreModule->getBasename());
                if (!array_key_exists($moduleName, $extractorKeys)) {
                    $extractorKeys[$moduleName] = new MessageCatalogue('cs');
                }
                $extractor->extract($incoreModule->getPathname(), $extractorKeys[$moduleName]);
            }
        }
        $this->updateTranslates($extractorKeys, 'front');

        $this->logFacade->create(LogActionEnum::Translate, 'translate');
    }

    /**
     * @param array<string, MessageCatalogue> $extractorKeys
     */
    private function updateTranslates(array $extractorKeys, string $source): void
    {
        $dataInsert = [];

        $allKeys = [];
        $allTypes = [];

        foreach ($extractorKeys as $module => $keys) {
            if($keys->getMetadata() !== null) {
                foreach ($keys->getMetadata() as $key => $data) {
                    if (!in_array($key, $allKeys)) {
                        $allKeys[] = $key;
                        $allTypes[] = Arrays::first($data['type']);
                    }
                }
            }
        }

        foreach ($allKeys as $index => $key) {
            $translate = $this->translateModel->getByKey($key);
            if (null === $translate) {
                $dataInsert[] = [
                    'key' => $key,
                    'source' => $source,
                    'type' => $allTypes[$index]->value,
                ];
            }else{
                $translate->update([
                    'source' => $source,
                    'type' => $allTypes[$index]->value,
                ]);
            }
        }

        if (!empty($dataInsert)) {
            $this->translateModel->insert($dataInsert);
        }
        $this->translateModel->getNotKeys($allKeys, $source)->delete();
    }

    public function create(Form\FormNewData $data):void
    {
        $translate = $this->translateModel->insert([
            'key' => $data->key,
            'source' => 'admin',
            'is_manual' => true,
        ]);
        $this->logFacade->create(LogActionEnum::Created, 'translate', $translate->id);
    }
}
