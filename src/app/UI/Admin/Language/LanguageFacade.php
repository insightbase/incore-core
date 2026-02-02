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
use App\Model\Admin\Blog;
use App\Model\Admin\BlogLanguage;
use App\Model\Admin\BlogTag;
use App\Model\Admin\ContactForm;
use App\Model\Admin\ContactFormRow;
use App\Model\Admin\ContactFormRowLanguage;
use App\Model\Admin\Content;
use App\Model\Admin\ContentBlockItemGallery;
use App\Model\Admin\ContentBlockItemText;
use App\Model\Admin\ContentFieldValue;
use App\Model\Admin\ContentFieldValueLanguage;
use App\Model\Admin\ContentLanguage;
use App\Model\Admin\ContentValue;
use App\Model\Admin\ContentValueItem;
use App\Model\Admin\Enumeration;
use App\Model\Admin\EnumerationItemValue;
use App\Model\Admin\EnumerationItemValueLanguage;
use App\Model\Admin\EnumerationRow;
use App\Model\Admin\EnumerationRowLanguage;
use App\Model\Admin\Language;
use App\Model\Admin\LanguageLocale;
use App\Model\Admin\LanguageTranslate;
use App\Model\Admin\Module;
use App\Model\Admin\Setting;
use App\Model\Admin\StaticPage;
use App\Model\Admin\StaticPageLanguage;
use App\Model\Admin\Tag;
use App\Model\Admin\TagLanguage;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\ContentLanguageEntity;
use App\Model\Entity\LanguageEntity;
use App\Model\Entity\TranslateEntity;
use App\Model\Enum\EnumerationFormTypeEnum;
use App\Model\Enum\TranslateTypeEnum;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Accessory\Blog\BlogContentTypeEnum;
use App\UI\Admin\Accessory\Blog\BlogDto;
use App\UI\Admin\Blog\BlogFacade;
use App\UI\Admin\Blog\Form\Entity\InputEntity;
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
    private int $bachLimit = 40;

    public function __construct(
        private readonly Language          $languageModel,
        private readonly Translator        $translator,
        private readonly LogFacade         $logFacade,
        private readonly EventFacade       $eventFacade,
        private readonly LinkGenerator     $linkGenerator,
        private readonly Translate         $translateModel,
        private readonly TranslateLanguage  $translateLanguageModel,
        private readonly Setting            $settingModel,
        private readonly ParameterBag       $parameterBag,
        private readonly Module             $moduleModel,
        private readonly Container          $container,
        private readonly Storage            $storage,
        private readonly LanguageTranslate  $languageTranslateModel,
        private readonly User               $userSecurity,
        private readonly LanguageLocale     $languageLocaleModel,
        private readonly StaticPage         $staticPageModel,
        private readonly StaticPageLanguage $staticPageLanguage,
    ) {}

    public function create(NewFormData $data): void
    {
        $newData = (array) $data;
        $languages = $this->languageModel->getToTranslateNotDefault()->fetchAll();
        foreach($languages as $locale){
            unset($newData['language_' . $locale->id]);
        }
        $language = $this->languageModel->insert($newData);
        foreach($languages as $locale){
            $languageLocaleData = (array)$data->{'language_' . $locale->id};
            if($languageLocaleData['name'] !== null) {
                $languageLocaleData['language_id'] = $language->id;
                $languageLocaleData['locale_id'] = $locale->id;
                $this->languageLocaleModel->insert($languageLocaleData);
            }
        }
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
        $updateData = (array) $data;
        foreach($this->languageModel->getToTranslateNotDefault() as $locale){
            unset($updateData['language_' . $locale->id]);
            $languageLocale = $this->languageLocaleModel->getByLanguageAndLocale($language, $locale);
            $languageLocaleData = (array)$data->{'language_' . $locale->id};
            if($languageLocale === null){
                if($languageLocaleData['name'] !== null) {
                    $languageLocaleData['language_id'] = $language->id;
                    $languageLocaleData['locale_id'] = $locale->id;
                    $this->languageLocaleModel->insert($languageLocaleData);
                }
            }else{
                if($languageLocaleData['name'] !== null) {
                    $languageLocale->update($languageLocaleData);
                }else{
                    $languageLocale->delete();
                }
            }
        }
        $language->update($updateData);
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
     *
     * @throws DefaultLanguageCannotByDeactivateException
     */
    public function changeActiveAdmin(ActiveRow $language): void
    {
        if ($language->active && $language->is_default) {
            throw new DefaultLanguageCannotByDeactivateException($this->translator->translate('flash_default_language_cannot_be_deactivate'));
        }

        $language->update(['active_admin' => !$language->active_admin]);
        $this->logFacade->create(LogActionEnum::ChangeActiveAdmin, 'language', $language->id);
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
            $this->addTranslateToJson($translate, $json, $defaultLanguage);
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

            /** @var ContentLanguage $contentLanguageModel */
            $contentLanguageModel = $this->container->getByType(ContentLanguage::class);

            $defaultLanguage = $this->languageModel->getDefault();

            foreach($contentLanguageModel->getByLanguage($language) as $contentLanguage) {
                $this->addPerformanceContentToJson($contentLanguage, $json, $defaultLanguage);
            }
        }

        if($this->moduleModel->getBySystemName('tag') !== null){
            /** @var Tag $tagModel */
            $tagModel = $this->container->getByType(Tag::class);

            foreach($tagModel->getTable() as $tag){
                $json['tag_' . $tag->id] = $tag->name;
            }
        }

        if($this->moduleModel->getBySystemName('blog') !== null){
            /** @var Blog $blogModel */
            $blogModel = $this->container->getByType(Blog::class);

            foreach($blogModel->getTable() as $blog){
                $content = BlogDto::fromArray(Json::decode($blog->content, true));
                foreach($content->content as $key => $value){
                    if($value->type === BlogContentTypeEnum::String){
                        $json['blog_' . $blog->id . '_' . $key] = $value->value;
                    }
                    if($value->type === BlogContentTypeEnum::EditorJs){
                        $json['blog_' . $blog->id . '_' . $key] = Json::decode($value->value);
                    }
                }
                $json['blog_' . $tag->id . '_name'] = $blog->name;
                $json['blog_' . $tag->id . '_slug'] = $blog->slug;
            }
        }

        foreach($this->languageModel->getTable() as $language1){
            $json['language_' . $language1->id] = $language1->name;
        }

        foreach($this->staticPageModel->getTable() as $staticPage){
            $json['static_page_' . $staticPage->id . '_name'] = $staticPage->name;
            $json['static_page_' . $staticPage->id . '_title'] = $staticPage->title;
            $json['static_page_' . $staticPage->id . '_description'] = $staticPage->description;
            $json['static_page_' . $staticPage->id . '_keywords'] = $staticPage->keywords;
            $json['static_page_' . $staticPage->id . '_content'] = Json::decode($staticPage->content, true);
        }

        $this->sendJsonToTranslate($json, $defaultLanguage, $language);
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
            /** @var ContentLanguage $contentLanguageModel */
            $contentLanguageModel = $this->container->getByType(ContentLanguage::class);
        }
        $tagModel = null;
        if($this->moduleModel->getBySystemName('tag') !== null) {
            /** @var Tag $tagModel */
            $tagModel = $this->container->getByType(Tag::class);
            /** @var TagLanguage $tagLanguageModel */
            $tagLanguageModel = $this->container->getByType(TagLanguage::class);
            /** @var BlogFacade $blogFacade */
            $blogFacade = $this->container->getByType(BlogFacade::class);
            /** @var BlogTag $blogTagModel */
            $blogTagModel = $this->container->getByType(BlogTag::class);
        }
        $blogModel = null;
        if($this->moduleModel->getBySystemName('blog') !== null) {
            /** @var Blog $blogModel */
            $blogModel = $this->container->getByType(Blog::class);
            /** @var BlogLanguage $blogLanguageModel */
            $blogLanguageModel = $this->container->getByType(BlogLanguage::class);
        }

        $blogsUpdated = [];

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
                    $contentBlockItemText?->update(['text' => $text]);
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
            }elseif($type === 'tag' && $tagModel !== null){
                $tag = $tagModel->get((int)$key);
                if($tag !== null){
                    $tagLanguage = $tagLanguageModel->getByTagAndLanguage($tag, $language);
                    if($tagLanguage === null){
                        $tagLanguageModel->insert([
                            'tag_id' => $tag->id,
                            'language_id' => $language->id,
                            'name' => $text,
                        ]);
                    }else{
                        $tagLanguage->update(['name' => $text]);
                    }

                    foreach($blogTagModel->getByTag($tag) as $blogTag){
                        $blogFacade->refreshContent($blogTag->blog);
                    }
                }
            }elseif($type === 'blog' && $blogModel !== null){
                $id = explode('_', $key);
                if(!in_array($id[0], $blogsUpdated)) {
                    $blog = $blogModel->get((int)$id[0]);
                    if ($blog !== null) {
                        $blogLanguage = $blogLanguageModel->getByBlogAndLanguage($blog, $language);
                        if ($blogLanguage !== null) {
                            $content = BlogDto::fromArray(Json::decode($blogLanguage->content, true));
                        } else {
                            $content = BlogDto::fromArray(Json::decode($blog->content, true));
                        }

                        $content->name = $json['blog_' . $blog->id . '_name'];
                        $content->slug = $blogLanguageModel->generateSlug($json['blog_' . $blog->id . '_slug'], $blog,$language,$blogLanguage);
                        foreach ($content->content as $key1 => $contentValue) {
                            if ($contentValue->type === BlogContentTypeEnum::String) {
                                $contentValue->value = $json['blog_' . $blog->id . '_' . $key1];
                                $content->content[$key1] = $contentValue;
                            }
                            if ($contentValue->type === BlogContentTypeEnum::EditorJs) {
                                $contentValue->value = Json::encode($json['blog_' . $blog->id . '_' . $key1]);
                                $content->content[$key1] = $contentValue;
                            }
                        }

                        if ($blogLanguage !== null) {
                            $blogLanguageModel->insert([
                                'name' => $content->name,
                                'slug' => $content->slug,
                                'content' => Json::encode($content),
                                'language_id' => $language->id,
                                'blog_id' => $blog->id,
                            ]);
                        }else {
                            $blogLanguage->update([
                                'name' => $content->name,
                                'slug' => $content->slug,
                                'content' => Json::encode($content),
                            ]);
                        }
                    }
                    $blogsUpdated[] = $blog->id;
                }
            }elseif($type === 'performanceContent'){
                $id = explode('_', $key);
                $contentLanguage = $contentLanguageModel->getByContentIdAndLanguageId((int)$id[0], $language->id);

                $data = [];
                if($id[1] === 'title'){
                    $data['title'] = $text;
                }
                if($id[1] === 'description'){
                    $data['description'] = $text;
                }

                if($contentLanguage === null){
                    $data['language_id'] = $language->id;
                    $data['content_id'] = (int)$key[0];
                    $contentLanguageModel->insert($data);
                }else{
                    $contentLanguage->update($data);
                }
            }elseif($type === 'language'){
                $locale = $this->languageModel->get((int)$key);
                if($locale !== null){
                    if($locale->is_default){
                        $locale->update(['name' => $text]);
                    }else {
                        $languageLocale = $this->languageLocaleModel->getByLanguageAndLocale($language, $locale);
                        if ($languageLocale === null) {
                            $this->languageLocaleModel->insert([
                                'language_id' => $language->id,
                                'locale_id' => $locale->id,
                                'name' => $text,
                            ]);
                        } else {
                            $languageLocale->update(['name' => $text]);
                        }
                    }
                }
            }elseif($type === 'static_page'){
                $id = explode('_', $key);
                $staticPage = $this->staticPageModel->get((int)$id);
                if($staticPage !== null){
                    if($key[1] === 'content'){
                        $text = Json::encode($text);
                    }
                    if($language->is_default){
                        $staticPage->update([
                            $key[0] => $text,
                        ]);
                    }else{
                        $staticPageLanguage = $this->staticPageLanguage->getByStaticPageAndLanguage($staticPage, $language);

                        if($staticPageLanguage === null){
                            $this->staticPageLanguage->insert([
                                'static_page_id' => $staticPage->id,
                                'language_id' => $language->id,
                                $key => $text
                            ]);
                        }else{
                            $staticPageLanguage->update([$key => $text]);
                        }
                    }
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

    private function addTranslateToJson(ActiveRow $translate, array &$json, ActiveRow $defaultLanguage):void{
        $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $defaultLanguage);
        if($translateLanguage !== null) {
            $value = $translateLanguage->value;
            if($translateLanguage->translate->type === TranslateTypeEnum::Html->value){
                $value = Json::decode($value, true);
            }
            $json['translate_' . $translate->key] = $value;
        }
    }

    /**
     * @param TranslateEntity $translate
     * @param LanguageEntity $language
     * @return void
     */
    public function translateTranslate(ActiveRow $translate, ActiveRow $language):void
    {
        $defaultLanguage = $this->languageModel->getDefault();
        $json = [];
        $this->addTranslateToJson($translate, $json, $defaultLanguage);

        $this->sendJsonToTranslate($json, $defaultLanguage, $language);
    }

    /**
     * @param array $json
     * @param LanguageEntity $defaultLanguage
     * @param LanguageEntity $language
     * @return void
     * @throws BasicAuthNotSetException
     * @throws GuzzleException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    private function sendJsonToTranslate(array $json, ActiveRow $defaultLanguage, ActiveRow $language):void
    {
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
                'request' => $body,
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

    public function translatePerformancesContent(ActiveRow $language):void
    {
        /** @var ContentLanguage $contentLanguageModel */
        $contentLanguageModel = $this->container->getByType(ContentLanguage::class);

        $defaultLanguage = $this->languageModel->getDefault();
        $json = [];

        foreach($contentLanguageModel->getByLanguage($language) as $contentLanguage) {
            $this->addPerformanceContentToJson($contentLanguage, $json, $defaultLanguage);
        }

        $this->sendJsonToTranslate($json, $defaultLanguage, $language);
    }

    /**
     * @param ContentLanguageEntity $contentLanguage
     * @param array $json
     * @param LanguageEntity $defaultLanguage
     * @return void
     */
    private function addPerformanceContentToJson(ActiveRow $contentLanguage, array &$json, ActiveRow $defaultLanguage):void
    {
        /** @var ContentLanguage $contentLanguageModel */
        $contentLanguageModel = $this->container->getByType(ContentLanguage::class);

        $contentLanguageDefault = $contentLanguageModel->getByContentIdAndLanguageId($contentLanguage->content_id, $defaultLanguage->id);
        if($contentLanguageDefault !== null){
            if($contentLanguageDefault->title !== null){
                $json['performanceContent_' . $contentLanguage->content_id . '_title'] = $contentLanguageDefault->title;
            }
            if($contentLanguageDefault->description !== null){
                $json['performanceContent_' . $contentLanguage->content_id . '_description'] = $contentLanguageDefault->description;
            }
        }
    }
}
