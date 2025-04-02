<?php

namespace App\UI\Admin\Translate\DataGrid;

use App\Component\Datagrid\Column\Column;
use App\Component\Translator\Translator;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;

readonly class InlineEdit implements \App\Component\Datagrid\InlineEdit
{
    public function __construct(
        private ActiveRow         $language,
        private Translate         $translateModel,
        private TranslateLanguage $translateLanguageModel,
        private Storage $storage,
    )
    {
    }

    public function isEnabled(ActiveRow $row): bool
    {
        return true;
    }

    public function getDefaults(int $id): array
    {
        $translate = $this->translateModel->get($id);
        $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $this->language);
        if($translateLanguage !== null){
            return ['value' => $translateLanguage->value];
        }else{
            return [];
        }
    }

    public function getOnSuccessCallback(): callable
    {
        return function(array $values):void{
            $translate = $this->translateModel->get($values['id']);
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $this->language);
            if ($translateLanguage !== null) {
                if ('' === $values['value'] || $values['value'] === null) {
                    $translateLanguage->delete();
                } else {
                    $translateLanguage->update(['value' => $values['value']]);
                }
            } else {
                if ('' !== $values['value'] && $values['value'] !== null) {
                    $this->translateLanguageModel->insert([
                        'translate_id' => $translate->id,
                        'language_id' => $this->language->id,
                        'value' => $values['value'],
                    ]);
                }
            }
            $cache = new Cache($this->storage, Translator::CACHE_NAMESPACE);
            $cache->remove($this->language->id);
        };
    }
}