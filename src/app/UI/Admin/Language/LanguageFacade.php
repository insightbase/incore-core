<?php

namespace App\UI\Admin\Language;

use App\Component\Front\ContactFormComponent\ContactFormControl;
use App\Component\Front\ContentControl\ContentControl;
use App\Component\Front\EnumerationControl\EnumerationControl;
use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Component\Translator\Translator;
use App\Event\EventFacade;
use App\Event\Language\ChangeDefaultEvent;
use App\Model\Admin\ContactForm;
use App\Model\Admin\ContactFormRow;
use App\Model\Admin\ContactFormRowLanguage;
use App\Model\Admin\Content;
use App\Model\Admin\ContentBlockItemGallery;
use App\Model\Admin\ContentBlockItemText;
use App\Model\Admin\ContentFieldValue;
use App\Model\Admin\ContentFieldValueLanguage;
use App\Model\Admin\ContentValue;
use App\Model\Admin\ContentValueItem;
use App\Model\Admin\Enumeration;
use App\Model\Admin\EnumerationItemValue;
use App\Model\Admin\EnumerationItemValueLanguage;
use App\Model\Admin\EnumerationRow;
use App\Model\Admin\EnumerationRowLanguage;
use App\Model\Admin\Language;
use App\Model\Admin\LanguageTranslate;
use App\Model\Admin\Module;
use App\Model\Admin\Setting;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\LanguageEntity;
use App\Model\Enum\EnumerationFormTypeEnum;
use App\Model\Enum\TranslateTypeEnum;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Content\Form\BlockItem\EditorJs;
use App\UI\Admin\Content\Form\BlockItem\Gallery;
use App\UI\Admin\Language\DataGrid\Exception\DefaultLanguageCannotByDeactivateException;
use App\UI\Admin\Language\Exception\BasicAuthNotSetException;
use App\UI\Admin\Language\Exception\LanguageCallbackIdNotFoundException;
use App\UI\Admin\Language\Exception\LanguageIsDefaultException;
use App\UI\Admin\Language\Exception\LanguageNotFoundException;
use App\UI\Admin\Language\Exception\TranslateInProgressException;
use App\UI\Admin\Language\Form\NewFormData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;
use Nette\Caching\Cache;
use Nette\Caching\Storage;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Container;
use Nette\Http\Url;
use Nette\Security\User;
use Nette\Utils\Arrays;
use Nette\Utils\DateTime;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class LanguageFacade
{
    private int $bachLimit = 50;

    public function __construct(
        private readonly Language          $languageModel,
        private readonly Translator        $translator,
        private readonly LogFacade         $logFacade,
        private readonly EventFacade       $eventFacade,
        private readonly LinkGenerator     $linkGenerator,
        private readonly Translate         $translateModel,
        private readonly TranslateLanguage $translateLanguageModel,
        private readonly Setting           $settingModel,
        private readonly ParameterBag      $parameterBag,
        private readonly Module            $moduleModel,
        private readonly Container         $container,
        private readonly Storage           $storage,
        private readonly LanguageTranslate $languageTranslateModel,
        private readonly User              $userSecurity,
    ) {}

    public function create(NewFormData $data): void
    {
        $language = $this->languageModel->insert((array) $data);
        $this->logFacade->create(LogActionEnum::Created, 'language', $language->id);
    }

    /**
     * @param LanguageEntity $language
     * @return void
     */
    public function delete(ActiveRow $language): void
    {
        $id = $language->id;
        $language->delete();
        $this->logFacade->create(LogActionEnum::Deleted, 'language', $id);
    }

    /**
     * @param LanguageEntity $language
     * @param Form\EditFormData $data
     * @return void
     */
    public function update(ActiveRow $language, \App\UI\Admin\Language\Form\EditFormData $data): void
    {
        $language->update((array) $data);
        $this->logFacade->create(LogActionEnum::Updated, 'language', $language->id);
    }

    /**
     * @param LanguageEntity $language
     */
    public function changeDefault(ActiveRow $language): void
    {
        $this->languageModel->getExplorer()->transaction(function () use ($language) {
            $default = $this->languageModel->getDefault();

            $this->languageModel->getTable()->update(['is_default' => false]);
            $language->update(['is_default' => true]);
            $event = new ChangeDefaultEvent($default, $language);
            $this->eventFacade->dispatch($event);
            $this->logFacade->create(LogActionEnum::ChangeDefault, 'language', $language->id);
        });
    }

    /**
     * @param LanguageEntity $language
     *
     * @throws DefaultLanguageCannotByDeactivateException
     */
    public function changeActive(ActiveRow $language): void
    {
        if ($language->active && $language->is_default) {
            throw new DefaultLanguageCannotByDeactivateException($this->translator->translate('flash_default_language_cannot_be_deactivate'));
        }

        $language->update(['active' => !$language->active]);
        $this->logFacade->create(LogActionEnum::ChangeActive, 'language', $language->id);
    }

    /**
     * @param LanguageEntity $language
     * @return void
     * @throws BasicAuthNotSetException
     * @throws TranslateInProgressException
     * @throws GuzzleException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translate(ActiveRow $language):void
    {
        $defaultLanguage = $this->languageModel->getDefault();

        $json = [];
        foreach($this->translateModel->getNotAdmin() as $translate){
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $defaultLanguage);
            if($translateLanguage !== null) {
                $value = $translateLanguage->value;
                if($translateLanguage->translate->type === TranslateTypeEnum::Html->value){
                    $value = Json::decode($value, true);
                }
                $json['translate_' . $translate->key] = $value;
            }
        }

        if($this->moduleModel->getBySystemName('enumeration') !== null){
            /** @var EnumerationRow $enumerationRowModel */
            $enumerationRowModel = $this->container->getByType(EnumerationRow::class);
            foreach($enumerationRowModel->getAll() as $enumerationRow){
                $json['enumeration_' . $enumerationRow->id] = $enumerationRow->name;
            }

            /** @var EnumerationItemValue $enumerationItemValueModel */
            $enumerationItemValueModel = $this->container->getByType(EnumerationItemValue::class);
            foreach($enumerationItemValueModel->getAll() as $enumerationItemValue){
                $value = $enumerationItemValue->value;
                if($enumerationItemValue->enumeration_row->type === EnumerationFormTypeEnum::EditorJs->value){
                    $value = Json::decode($value, true);
                }
                $json['enumerationItemValue_' . $enumerationItemValue->id] = $value;
            }
        }

        if($this->moduleModel->getBySystemName('forms') !== null){
            /** @var ContactFormRow $contactFormRowModel */
            $contactFormRowModel = $this->container->getByType(ContactFormRow::class);
            foreach($contactFormRowModel->getAll() as $contactFormRow){
                $json['contactForm_' . $contactFormRow->id] = $contactFormRow->name;
            }
        }

        if($this->moduleModel->getBySystemName('content') !== null){
            /** @var ContentBlockItemText $contentBlockItemTextModel */
            $contentBlockItemTextModel = $this->container->getByType(ContentBlockItemText::class);
            /** @var ContentValue $contentValueModel */
            $contentValueModel = $this->container->getByType(ContentValue::class);
            /** @var ContentValueItem $contentValueItemModel */
            $contentValueItemModel = $this->container->getByType(ContentValueItem::class);
            /** @var EditorJs $editorJsBlockItem */
            $editorJsBlockItem = $this->container->getByType(EditorJs::class);
            /** @var Gallery $galleryBlockItem */
            $galleryBlockItem = $this->container->getByType(Gallery::class);
            /** @var ContentBlockItemGallery $contentBlockItemGalleryModel */
            $contentBlockItemGalleryModel = $this->container->getByType(ContentBlockItemGallery::class);

            foreach($contentValueModel->getByLanguage($defaultLanguage) as $contentValue){
                $contentValueLng = $contentValueModel->getByContentBlockIdAndContentIdAndLanguageId($contentValue->content_block_id, $contentValue->content_id, $language->id);
                if($contentValueLng === null){
                    $data = $contentValue->toArray();
                    unset($data['id']);
                    $data['language_id'] = $language->id;
                    $data['content_value_base_language_id'] = $contentValue->id;
                    $contentValueLng = $contentValueModel->insert($data);
                }else{
                    $contentValueLng->update(['content_value_base_language_id' => $contentValue->id]);
                }

                foreach($contentValueItemModel->getByContentValue($contentValue) as $contentValueItem){
                    $contentValueItemLng = $contentValueItemModel->getByContentValueAndContentBlockItem($contentValueLng, $contentValueItem->content_block_item);
                    if($contentValueItemLng === null){
                        $contentValueItemLng = $contentValueItemModel->insert([
                            'content_value_id' => $contentValueLng->id,
                            'content_block_item_id' => $contentValueItem->content_block_item_id,
                            'content_value_item_base_language_id' => $contentValueItem->id,
                        ]);
                    }else{
                        $contentValueItemLng->update(['content_value_item_base_language_id' => $contentValueItem->id]);
                    }

                    if($contentValueItem->content_block_item->type === $galleryBlockItem->getSystemName()){
                        $contentBlockItemGalleryItems = $contentBlockItemGalleryModel->getByContentValueItem($contentValueItem);
                        $contentBlockItemGalleryItemsLng = $contentBlockItemGalleryModel->getByContentValueItem($contentValueItemLng);

                        if($contentBlockItemGalleryItems->count('*') > 0 && $contentBlockItemGalleryItemsLng->count('*') === 0) {
                            foreach ($contentBlockItemGalleryModel->getByContentValueItem($contentValueItem) as $contentBlockItemGallery) {
                                $data = $contentBlockItemGallery->toArray();
                                unset($data['id']);
                                $data['content_value_item_id'] = $contentValueItemLng->id;
                                $contentBlockItemGalleryModel->insert($data);
                            }
                        }
                    }

                    $contentBlockItemText = $contentBlockItemTextModel->getByContentValueItem($contentValueItem);
                    if($contentBlockItemText !== null){
                        $contentBlockItemTextLng = $contentBlockItemTextModel->getByContentValueItem($contentValueItemLng);
                        if($contentBlockItemTextLng === null){
                            $data = $contentBlockItemText->toArray();
                            unset($data['id']);
                            $data['content_value_item_id'] = $contentValueItemLng->id;
                            $contentBlockItemTextLng = $contentBlockItemTextModel->insert($data);
                        }

                        $text = $contentBlockItemText->text;
                        if($contentValueItem->content_block_item->type === $editorJsBlockItem->getSystemName()){
                            $text = Json::decode($text, true);
                        }

                        $json['contentBlockItemText_' . $contentBlockItemTextLng->id] = $text;
                    }
                }
            }

            /** @var ContentFieldValue $contentFieldValueModel */
            $contentFieldValueModel = $this->container->getByType(ContentFieldValue::class);
            /** @var \App\UI\Admin\Content\Form\FieldType\EditorJs $editorJsFieldType */
            $editorJsFieldType = $this->container->getByType(\App\UI\Admin\Content\Form\FieldType\EditorJs::class);
            foreach($contentFieldValueModel->getTable() as $contentFieldValue){
                $value = $contentFieldValue->value;
                if($contentFieldValue->content_field->type === $editorJsFieldType->getSystemName()){
                    $value = Json::decode($value, true);
                }
                $json['contentFieldValue_' . $contentFieldValue->id] = $value;
            }
        }

        $chunks = array_chunk($json, $this->bachLimit, true);

        $tempFile = $this->parameterBag->tempDir . '/language_api_' . time();
        $iterator = 0;
        foreach($chunks as $shortJson) {
            $callback = $this->linkGenerator->link('Admin:LanguageCallback:translate', ['id' => $language->id]);
            $setting = $this->settingModel->getDefault();
            if (array_key_exists('REDIRECT_REMOTE_USER', $_SERVER)) {
                if ($setting?->basic_auth_user === null || $setting?->basic_auth_password === null) {
                    throw new BasicAuthNotSetException();
                }
                $callback = new Url($callback);
                $callback->setUser($setting->basic_auth_user);
                $callback->setPassword($setting->basic_auth_password);
                $callback = (string)$callback;
            }

            $body = Json::encode($bodyArray = [
                'inputLocale' => $defaultLanguage->url,
                'outputLocale' => $language->url,
                'model' => 'flash',
                'callback' => $callback,
                'mode' => 'async',
                'value' => $shortJson,
            ]);

            $url = 'https://core.inbs.cz/api/gen/translate';

            FileSystem::write($tempFile . '_' . $iterator, Json::encode([
                'callback' => $callback,
                'url' => $url,
                'body' => $bodyArray,
            ]));

            $client = new Client();
            $response = $client->request('POST', $url, [
                'headers' => [
                    'access-token' => 'c9394c041d8e52ce109fec90f343ff6baf9eb52dc8a30879b373bcbd1948a403',
                    'store' => 'incore',
                    'content-type' => 'application/json',
                ],
                'body' => $body,
            ]);

            $response = Json::decode((string)$response->getBody(), true);
            $this->languageTranslateModel->insert([
                'drop_core_id' => $response['id'],
                'user_id' => $this->userSecurity->getId(),
                'language_id' => $language->id,
                'datetime' => new DateTime(),
            ]);

            FileSystem::write($tempFile . '_' . $iterator, Json::encode([
                'drop_core_id' => $response['id'],
                'callback' => $callback,
                'url' => $url,
                'body' => $bodyArray,
            ]));

            $iterator++;
        }
    }

    /**
     * @param int $id
     * @param array $post
     * @return void
     * @throws LanguageCallbackIdNotFoundException
     * @throws LanguageIsDefaultException
     * @throws LanguageNotFoundException
     * @throws \Nette\Utils\JsonException
     */
    public function processDropCoreCallback(int $id, array $post):void
    {
        $language = $this->languageModel->get($id);
        if($language === null){
            throw new LanguageNotFoundException();
        }
        if($language->is_default){
            throw new LanguageIsDefaultException();
        }
        $languageTranslate = $this->languageTranslateModel->getByDropCoreId($post['id']);
        if($languageTranslate === null){
            throw new LanguageCallbackIdNotFoundException();
        }

        $enumerationRowLanguageModel = null;
        $enumerationItemValueLanguageModel = null;
        if($this->moduleModel->getBySystemName('enumeration') !== null) {
            /** @var EnumerationRowLanguage $enumerationRowLanguageModel */
            $enumerationRowLanguageModel = $this->container->getByType(EnumerationRowLanguage::class);
            /** @var EnumerationItemValueLanguage $enumerationItemValueLanguageModel */
            $enumerationItemValueLanguageModel = $this->container->getByType(EnumerationItemValueLanguage::class);
        }
        $contactFormRowLanguageModel = null;
        if($this->moduleModel->getBySystemName('forms') !== null) {
            /** @var ContactFormRowLanguage $contactFormRowLanguageModel */
            $contactFormRowLanguageModel = $this->container->getByType(ContactFormRowLanguage::class);
        }

        $contentBlockItemTextModel = null;
        if($this->moduleModel->getBySystemName('content') !== null) {
            /** @var ContentBlockItemText $contentBlockItemTextModel */
            $contentBlockItemTextModel = $this->container->getByType(ContentBlockItemText::class);
            /** @var ContentFieldValueLanguage $contentFieldValueLanguageModel */
            $contentFieldValueLanguageModel = $this->container->getByType(ContentFieldValueLanguage::class);
            /** @var EnumerationItemValue $enumerationItemValueModel */
            $enumerationItemValueModel = $this->container->getByType(EnumerationItemValue::class);
        }

        $json = $post['value'];
        $firstKey = Arrays::firstKey($json);
        if($firstKey === '0' || $firstKey === 0){
            $json = $json[0];
        }
        foreach($json as $key => $text){
            $key = explode('_', $key);
            $type = Arrays::pick($key, 0);
            $key = implode('_', $key);

            if($type === 'translate'){
                $translate = $this->translateModel->getByKey($key);
                if($translate->type === TranslateTypeEnum::Html->value){
                    $text = Json::encode($text);
                }
                if($translate !== null){
                    $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $language);
                    if($translateLanguage === null){
                        $this->translateLanguageModel->insert([
                            'value' => $text,
                            'language_id' => $language->id,
                            'translate_id' => $translate->id,
                        ]);
                    }else{
                        $translateLanguage->update(['value' => $text]);
                    }
                }
            }elseif($type === 'enumeration' && $enumerationRowLanguageModel !== null){
                $enumerationRowLanguage = $enumerationRowLanguageModel->getByEnumerationRowIdAndLanguage((int)$key, $language);
                if($enumerationRowLanguage === null){
                    $enumerationRowLanguageModel->insert([
                        'name' => $text,
                        'language_id' => $language->id,
                        'enumeration_row_id' => (int)$key,
                    ]);
                }else {
                    $enumerationRowLanguage->update(['name' => $text]);
                }
            }elseif($type === 'enumerationItemValue' && $enumerationItemValueLanguageModel !== null){
                $enumerationItemValueLanguage = $enumerationItemValueLanguageModel->getByEnumerationItemValueIdAndLanguage((int)$key, $language);

                if($enumerationItemValueLanguage === null){
                    $enumerationItemValue = $enumerationItemValueModel->get((int)$key);
                    if($enumerationItemValue->enumeration_row->type === EnumerationFormTypeEnum::EditorJs->value){
                        $text = Json::encode($text);
                    }
                    $enumerationItemValueLanguageModel->insert([
                        'value' => $text,
                        'language_id' => $language->id,
                        'enumeration_item_value_id' => (int)$key,
                    ]);
                }else {
                    if($enumerationItemValueLanguage->enumeration_item_value->enumeration_row->type === EnumerationFormTypeEnum::EditorJs->value){
                        $text = Json::encode($text);
                    }
                    $enumerationItemValueLanguage->update(['value' => $text]);
                }
            }elseif($type === 'contactForm' && $contactFormRowLanguageModel !== null){
                $contactFormRowLanguage = $contactFormRowLanguageModel->getByContactFormRowIdAndLanguage((int)$key, $language);
                if($contactFormRowLanguage === null){
                    $contactFormRowLanguageModel->insert([
                        'name' => $text,
                        'contact_form_row_id' => (int)$key,
                        'language_id' => $language->id,
                    ]);
                }else {
                    $contactFormRowLanguage->update(['name' => $text]);
                }
            }elseif($type === 'contentBlockItemText' && $contentBlockItemTextModel !== null){
                $contentBlockItemText = $contentBlockItemTextModel->get((int)$key);
                if($contentBlockItemText !== null){
                    /** @var EditorJs $editorJsBlockItem */
                    $editorJsBlockItem = $this->container->getByType(EditorJs::class);
                    if($contentBlockItemText->content_value_item->content_block_item->type === $editorJsBlockItem->getSystemName()){
                        $text = Json::encode($text);
                    }
                    $contentBlockItemText?->update(['value' => $text]);
                }
            }elseif($type === 'contentFieldValue' && $contentBlockItemTextModel !== null){
                /** @var \App\UI\Admin\Content\Form\FieldType\EditorJs $editorJsFieldType */
                $editorJsFieldType = $this->container->getByType(\App\UI\Admin\Content\Form\FieldType\EditorJs::class);

                $contentFieldValueLanguage = $contentFieldValueLanguageModel->getByContentIdAndLanguage((int)$key, $language);
                if($contentFieldValueLanguage === null){
                    /** @var ContentFieldValue $contentFieldValueModel */
                    $contentFieldValueModel = $this->container->getByType(ContentFieldValue::class);
                    $contentFieldValue = $contentFieldValueModel->get((int)$key);
                    if($contentFieldValue->content_field->type === $editorJsFieldType->getSystemName()){
                        $text = Json::encode($text);
                    }

                    $contentFieldValueLanguageModel->insert([
                        'content_field_value_id' => (int)$key,
                        'language_id' => $language->id,
                        'value' => $text,
                    ]);
                }else{
                    if($contentFieldValueLanguage->content_field_value->content_field->type === $editorJsFieldType->getSystemName()){
                        $text = Json::encode($text);
                    }

                    $contentFieldValueLanguage->update(['value' => $text]);
                }
            }
        }
        $languageTranslate->update(['finished' => new DateTime()]);

        $cacheTranslate = new Cache($this->storage, Translator::CACHE_NAMESPACE);
        $cacheTranslate->remove($language->id);

        if($enumerationRowLanguageModel !== null){
            $cache = new Cache($this->storage, EnumerationControl::CACHE_NAMESPACE);
            /** @var Enumeration $enumerationModel */
            $enumerationModel = $this->container->getByType(Enumeration::class);
            foreach($enumerationModel->getAll() as $enumeration) {
                $cache->clean([Cache::Tags => ['enumerationType' => $enumeration->internal_name]]);
            }
        }

        if($contactFormRowLanguageModel !== null){
            $cache = new Cache($this->storage, ContactFormControl::CACHE_NAMESPACE_ROW);
            /** @var ContactForm $contactFormModel */
            $contactFormModel = $this->container->getByType(ContactForm::class);
            foreach($contactFormModel->getAll() as $contactForm) {
                $cache->remove('contactFormRow-' . $contactForm->id . '-' . $language->id);
            }
        }

        if($contentBlockItemTextModel !== null){
            $cache = new Cache($this->storage, ContentControl::CACHE_NAMESPACE);

            /** @var Content $contentModel */
            $contentModel = $this->container->getByType(Content::class);
            foreach($contentModel->getTable() as $content) {
                $cache->getStorage()->clean([
                    Cache::Tags => ['content_id_' . $content->id],
                ]);
            }
        }
    }
}
