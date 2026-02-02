<?php

namespace App\UI\Admin\StaticPage;

use App\Component\Front\StaticPageControl\StaticPageControl;
use App\Model\Admin\Language;
use App\Model\Admin\StaticPage;
use App\Model\Admin\StaticPageLanguage;
use App\Model\Entity\StaticPageEntity;
use Nette\Caching\Cache;
use Nette\Caching\Storage;

readonly class StaticPageFacade
{
    public function __construct(
        private Language $languageModel,
        private StaticPage $staticPageModel,
        private StaticPageLanguage $staticPageLanguageModel,
        private Storage $storage,
    )
    {
    }

    /**
     * @param StaticPageEntity $staticPage
     * @param bool $active
     * @return void
     */
    public function changeActive(\Nette\Database\Table\ActiveRow $staticPage, bool $active):void
    {
        $staticPage->update(['active' => $active]);
    }

    /**
     * @param StaticPageEntity $staticPage
     * @return void
     */
    public function delete(\Nette\Database\Table\ActiveRow $staticPage):void
    {
        $staticPage->delete();
    }

    public function create(Form\NewData $data):void
    {
        $insertData = (array)$data;

        $language = $this->languageModel->getDefault();
        $langData = (array)$data->{'language_' . $language->id};
        $langData = array_merge($langData, (array)$data->{'language_' . $language->id}->{'seo_language_' . $language->id});

        $insertData = array_merge($insertData, $langData);
        $iterator = '';
        while($this->staticPageModel->getBySlug($langData['slug'] . $iterator)){
            if($iterator === ''){
                $iterator = 1;
            }else{
                $iterator++;
            }
        }
        $insertData['slug'] = $langData['slug'] . $iterator;

        foreach($this->languageModel->getToTranslate() as $language1) {
            unset($insertData['language_' . $language1->id]);
            unset($insertData['seo_language_' . $language1->id]);
        }

        $staticPage = $this->staticPageModel->insert($insertData);

        foreach($this->languageModel->getToTranslateNotDefault() as $language1){
            $langData = (array)$data->{'language_' . $language1->id};
            $langData = array_merge($langData, (array)$langData['seo_language_' . $language1->id]);
            $langData['static_page_id'] = $staticPage->id;
            $langData['language_id'] = $language1->id;
            unset($langData['seo_language_' . $language1->id]);
            $this->staticPageLanguageModel->insert($langData);
        }
    }

    /**
     * @param StaticPageEntity $staticPage
     * @param Form\EditData $data
     * @return void
     */
    public function update(\Nette\Database\Table\ActiveRow $staticPage, Form\EditData $data):void
    {
        $updateData = (array)$data;
        foreach($this->languageModel->getToTranslate() as $language){
            unset($updateData['language_' . $language->id]);
        }
        $defaultLanguage= $this->languageModel->getDefault();
        $updateData = array_merge($updateData, (array)$data->{'language_' . $defaultLanguage->id});
        unset($updateData['seo_language_' . $defaultLanguage->id]);
        $staticPage->update($updateData);

        foreach($this->languageModel->getToTranslateNotDefault() as $language){
            $langData = (array)$data->{'language_' . $language->id};
            $langData = array_merge($langData, (array)$langData['seo_language_' . $language->id]);

            unset($langData['seo_language_' . $language->id]);

            $staticPageLanguage = $this->staticPageLanguageModel->getByStaticPageAndLanguage($staticPage, $language);
            if($staticPageLanguage === null) {
                $langData['static_page_id'] = $staticPage->id;
                $langData['language_id'] = $language->id;
                $this->staticPageLanguageModel->insert($langData);
            }else{
                $staticPageLanguage->update($langData);
            }
        }

        $cache = new Cache($this->storage, StaticPageControl::CACHE_NAMESPACE);
        $cache->clean([Cache::Tags => ['static_page_' . $staticPage->system_name]]);
    }
}