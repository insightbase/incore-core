<?php

namespace App\UI\Translate;

use App\Component\Translator\Extractor\LatteExtractor;
use App\Component\Translator\Extraxtor\NetteTranslatorExtractor;
use App\Model\Entity\TranslateEntity;
use App\Model\Language;
use App\Model\Module;
use App\Model\Translate;
use App\Model\TranslateLanguage;
use App\UI\Accessory\ParameterBag;
use App\UI\Translate\Form\FormTranslateData;
use Nette\Database\Table\ActiveRow;
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
    )
    {
    }

    /**
     * @param TranslateEntity $translate
     * @param FormTranslateData $data
     * @return void
     */
    public function translate(ActiveRow $translate, FormTranslateData $data):void
    {
        foreach($this->languageModel->getToTranslate() as $language){
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
            if($translateLanguage){
                if($data->language[$language->id] === null){
                    $translateLanguage->delete();
                }else{
                    $translateLanguage->update(['value' => $data->language[$language->id]]);
                }
            }else{
                if($data->language[$language->id] !== null){
                    $this->translateLanguageModel->insert([
                        'value' => $data->language[$language->id],
                        'translate_id' => $translate->id,
                        'language_id' => $language->id
                    ]);
                }
            }
        }
    }

    public function synchronize(): void
    {
        $extractor = new ChainExtractor();
        $extractor->addExtractor('latte', $this->latteExtractor);
        $extractor->addExtractor('php', $this->netteTranslatorExtractor);

        $vendorIncoreDir = $this->parameterBag->appDir . '/../vendor/incore/';

        $extractorKeys = [];

        foreach(\Nette\Utils\Finder::findDirectories('*')->in($vendorIncoreDir) as $incoreModule) {
            $module = $this->moduleModel->getBySystemName($incoreModule->getBasename());
            if($module === null){
                $moduleName = 'core';
            }else{
                $moduleName = $module->system_name;
            }
            if(!array_key_exists($moduleName, $extractorKeys)){
                $extractorKeys[$moduleName] = new MessageCatalogue('cs');
            }
            $extractor->extract($incoreModule->getPathname(), $extractorKeys[$moduleName]);
        }

        $this->updateTranslates($extractorKeys);
    }

    /**
     * @param array<string, MessageCatalogue> $extractorKeys
     * @return void
     */
    private function updateTranslates(array $extractorKeys):void{
        $dataInsert = [];

        $allKeys = [];

        foreach($extractorKeys as $module => $keys){
            foreach($keys->getMetadata() as $key => $data){
                if(!in_array($key, $allKeys)) {
                    $allKeys[] = $key;
                }
            }
        }

        foreach($allKeys as $key){
            $translate = $this->translateModel->getByKey($key);
            if($translate === null){
                $dataInsert[] = [
                    'key' => $key,
                ];
            }
        }

        if(!empty($dataInsert)) {
            $this->translateModel->insert($dataInsert);
        }
        $this->translateModel->getNotKeys($allKeys)->delete();
    }
}