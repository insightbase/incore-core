<?php

namespace App\UI\Admin\Language;

use App\Component\DropCore\DropCoreConfigProvider;
use App\Component\Front\ContactFormComponent\ContactFormControl;
use App\Component\Front\ContentControl\ContentControl;
use App\Component\Front\EnumerationControl\EnumerationControl;
use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Component\Translator\Translator;
use App\Event\EventFacade;
use App\Event\Language\ChangeDefaultEvent;
use App\Model\Admin\ContactForm;
use App\Model\Admin\Content;
use App\Model\Admin\ContentLanguage;
use App\Model\Admin\Enumeration;
use App\Model\Admin\Language;
use App\Model\Admin\LanguageLocale;
use App\Model\Admin\LanguageTranslate;
use App\Model\Admin\Module;
use App\Model\Admin\Setting;
use App\Model\Entity\ContentLanguageEntity;
use App\Model\Entity\LanguageEntity;
use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Language\DataGrid\Exception\DefaultLanguageCannotByDeactivateException;
use App\UI\Admin\Language\Exception\BasicAuthNotSetException;
use App\UI\Admin\Language\Exception\LanguageIsDefaultException;
use App\UI\Admin\Language\Exception\LanguageNotFoundException;
use App\UI\Admin\Language\Exception\NotEnoughCreditsException;
use App\UI\Admin\Language\Exception\TranslateApiException;
use App\UI\Admin\Language\Exception\TranslateInProgressException;
use App\UI\Admin\Language\Form\NewFormData;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
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

    /**
     * Když je nastaveno, dávka se místo odeslání do DropCore předá tomuto
     * callable. Používá ověřovací skript translation_snapshot.php, aby se
     * daly porovnat klíče před migrací a po ní bez čerpání kreditů.
     *
     * @var ?callable(array<string, mixed>): void
     */
    public $dryRunCallback = null;

    public function __construct(
        private readonly Language          $languageModel,
        private readonly Translator        $translator,
        private readonly LogFacade         $logFacade,
        private readonly EventFacade       $eventFacade,
        private readonly LinkGenerator     $linkGenerator,
        private readonly Setting            $settingModel,
        private readonly ParameterBag       $parameterBag,
        private readonly Module             $moduleModel,
        private readonly Container          $container,
        private readonly Storage            $storage,
        private readonly LanguageTranslate  $languageTranslateModel,
        private readonly User               $userSecurity,
        private readonly LanguageLocale     $languageLocaleModel,
        private readonly DropCoreConfigProvider $dropCoreConfigProvider,
        private readonly \App\Component\Translation\TranslationProviderRegistry $translationProviderRegistry,
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
     * Odešle k překladu texty, které v jazyce `$language` ještě přeložené nejsou.
     *
     * @param LanguageEntity $language
     * @return int počet odeslaných textů
     * @throws BasicAuthNotSetException
     * @throws TranslateInProgressException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translate(ActiveRow $language):int
    {
        $defaultLanguage = $this->languageModel->getDefault();

        $json = [];

        // Obsah bloků a polí obsahu je zdroj překladu App\Component\Translation\ContentTranslationProvider
        // (viz $this->translationProviderRegistry->collectAll() níže). Zde zůstává jen obsah vázaný
        // na performance (title/description v ContentLanguage) — ten mezi zdroje registrované přes
        // TranslationProvider nepatří, viz i translatePerformancesContent().
        if($this->moduleModel->getBySystemName('content') !== null){
            /** @var ContentLanguage $contentLanguageModel */
            $contentLanguageModel = $this->container->getByType(ContentLanguage::class);

            foreach($contentLanguageModel->getByLanguage($language) as $contentLanguage) {
                $this->addPerformanceContentToJson($contentLanguage, $json, $defaultLanguage, true);
            }
        }

        // Hromadný překlad posílá jen to, co v cílovém jazyce ještě přeložené není.
        $json += $this->translationProviderRegistry->collectAll($language, true);

        if ($json !== []) {
            $this->sendJsonToTranslate($json, $defaultLanguage, $language);
        }

        return count($json);
    }

    /**
     * Přeloží jedinou položku zdroje registrovaného přes TranslationProvider.
     * Obálka nad translateProviderItems() pro čitelnost volání z presenterů.
     *
     * @param string $systemName
     * @param int $id
     * @param LanguageEntity $language
     * @return void
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translateProviderItem(string $systemName, int $id, ActiveRow $language): void
    {
        $this->translateProviderItems($systemName, [$id], $language);
    }

    /**
     * Přeloží vyjmenované položky jednoho zdroje. Všechny odejdou v jedné dávce,
     * takže překlad stovky položek nestojí sto samostatných volání API.
     *
     * @param string $systemName
     * @param int[] $ids
     * @param LanguageEntity $language
     * @return void
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translateProviderItems(string $systemName, array $ids, ActiveRow $language): void
    {
        $provider = $this->translationProviderRegistry->get($systemName);
        if ($provider === null || $ids === []) {
            return;
        }

        $json = [];
        foreach ($ids as $id) {
            foreach ($provider->collect($language, $id) as $item) {
                $json[\App\Component\Translation\TranslationKey::encode($systemName, $item->id, $item->field)] = $item->value;
            }
        }

        if ($json === []) {
            return;
        }

        $defaultLanguage = $this->languageModel->getDefault();
        if ($defaultLanguage === null) {
            return;
        }

        $this->sendJsonToTranslate($json, $defaultLanguage, $language);
    }

    /**
     * @param int $id
     * @param array $post
     * @return void
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
        // Rychlé (demo) DropCore vystřelí callback dřív, než se stihne uložit language_translate
        // záznam (insert je až po odpovědi na požadavek). Záznam slouží jen k označení `finished`,
        // takže když ještě není, překlad přesto aplikujeme a `finished` nastavíme best-effort níže.
        $languageTranslate = $this->languageTranslateModel->getByDropCoreId($post['id']);

        // $contentLanguageModel slouží jen větvi 'performanceContent' níže (obsah bloků a polí
        // obsahu už zpracuje App\Component\Translation\ContentTranslationProvider výše ve smyčce)
        // a jako příznak, že je modul content zapnutý, pro mazání cache na konci metody.
        $contentLanguageModel = null;
        if($this->moduleModel->getBySystemName('content') !== null) {
            /** @var ContentLanguage $contentLanguageModel */
            $contentLanguageModel = $this->container->getByType(ContentLanguage::class);
        }
        $json = $post['value'];
        $firstKey = Arrays::firstKey($json);
        if($firstKey === '0' || $firstKey === 0){
            $json = $json[0];
        }
        foreach($json as $key => $text){
            $originalKey = (string) $key;
            $translationKey = \App\Component\Translation\TranslationKey::tryDecode($originalKey);
            if ($translationKey !== null) {
                $provider = $this->translationProviderRegistry->get($translationKey->systemName);
                if ($provider === null) {
                    // Zdroj překladu mezitím zmizel; callback je asynchronní a nemá
                    // komu chybu ohlásit, proto jen zaznamenáme a pokračujeme dál.
                    \Tracy\Debugger::log(sprintf('Neznámý zdroj překladu "%s" v callbacku.', $translationKey->systemName), \Tracy\ILogger::WARNING);
                    continue;
                }

                try {
                    $provider->save($translationKey->id, $translationKey->field, $text, $language);
                } catch (\Throwable $e) {
                    \Tracy\Debugger::log($e, \Tracy\ILogger::EXCEPTION);
                }

                continue;
            }

            $key = explode('_', $key);
            $type = Arrays::pick($key, 0);
            $key = implode('_', $key);

            // Obsah bloků a polí obsahu (dřívější typy 'contentBlockItemText' a 'contentFieldValue')
            // teď přichází jako klíč zdroje překladu (TranslationKey) a zpracuje ho
            // App\Component\Translation\ContentTranslationProvider::save() výše ve smyčce.
            // 'performanceContent' zůstává — obsah vázaný na performance se nemigruje.
            // $contentLanguageModel je null, pokud modul content v instalaci vůbec není
            // (translatePerformancesContent() klíče 'performanceContent' generuje bez ohledu
            // na to, protože je volaná jen z prezenteru performance) — v takovém (prakticky
            // nedosažitelném) případě se klíč jen tiše přeskočí, místo aby spadl na nedefinovanou proměnnou.
            if($type === 'performanceContent' && $contentLanguageModel !== null){
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
                    $data['content_id'] = (int)$id[0];
                    $contentLanguageModel->insert($data);
                }else{
                    $contentLanguage->update($data);
                }
            } elseif ($type !== 'performanceContent') {
                // Klíč neodpovídá ani novému formátu (TranslationKey), ani žádné zbylé staré větvi
                // podle prefixu - dřív by tiše zmizel beze stopy, což skrývalo chyby jako ta se
                // statickými stránkami. Zalogujeme, ať se podobné selhání příště nepřehlédne.
                \Tracy\Debugger::log(sprintf('Nerozpoznaný klíč překladu "%s" v callbacku.', $originalKey), \Tracy\ILogger::WARNING);
            }
        }
        $languageTranslate?->update(['finished' => new DateTime()]);

        $cacheTranslate = new Cache($this->storage, Translator::CACHE_NAMESPACE);
        $cacheTranslate->remove($language->id);

        if($this->moduleModel->getBySystemName('enumeration') !== null){
            $cache = new Cache($this->storage, EnumerationControl::CACHE_NAMESPACE);
            /** @var Enumeration $enumerationModel */
            $enumerationModel = $this->container->getByType(Enumeration::class);
            foreach($enumerationModel->getAll() as $enumeration) {
                $cache->clean([Cache::Tags => ['enumerationType' => $enumeration->internal_name]]);
            }
        }

        if($this->moduleModel->getBySystemName('forms') !== null){
            $cache = new Cache($this->storage, ContactFormControl::CACHE_NAMESPACE_ROW);
            /** @var ContactForm $contactFormModel */
            $contactFormModel = $this->container->getByType(ContactForm::class);
            foreach($contactFormModel->getAll() as $contactForm) {
                $cache->remove('contactFormRow-' . $contactForm->id . '-' . $language->id);
            }
        }

        if($contentLanguageModel !== null){
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

    /**
     * @param array $json
     * @param LanguageEntity $defaultLanguage
     * @param LanguageEntity $language
     * @return void
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    private function sendJsonToTranslate(array $json, ActiveRow $defaultLanguage, ActiveRow $language):void
    {
        if ($this->dryRunCallback !== null) {
            ($this->dryRunCallback)($json);

            return;
        }

        $chunks = array_chunk($json, $this->bachLimit, true);
        $totalChunks = count($chunks);

        // Kredity i překlady používají stejný klíč z nastavení (identity token + store + prostředí).
        $dropCoreConfig = $this->dropCoreConfigProvider->getConfig();
        if (null === $dropCoreConfig) {
            throw new TranslateApiException(
                'Pro překlad je potřeba v nastavení vyplnit identity token a prostředí (DropCore).',
                0,
                $totalChunks,
            );
        }

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

            $url = $dropCoreConfig->apiUrl . '/gen/translate';

            FileSystem::write($tempFile . '_' . $iterator, Json::encode([
                'callback' => $callback,
                'url' => $url,
                'body' => $bodyArray,
            ]));

            try {
                $client = new Client();
                $response = $client->request('POST', $url, [
                    'headers' => [
                        'identity-token' => $dropCoreConfig->identityToken,
                        'store' => $dropCoreConfig->store,
                        'content-type' => 'application/json',
                    ],
                    'body' => $body,
                ]);
            } catch (GuzzleException $e) {
                // 402 Payment Required = na účtu není dost kreditů na překlad.
                if ($e instanceof RequestException && 402 === $e->getResponse()?->getStatusCode()) {
                    throw new NotEnoughCreditsException(
                        'Na překlad není dostatek kreditů.',
                        $iterator,
                        $totalChunks,
                        $e,
                    );
                }

                throw new TranslateApiException(
                    'Volání překladového API selhalo: ' . $e->getMessage(),
                    $iterator,
                    $totalChunks,
                    $e,
                );
            }

            try {
                $response = Json::decode((string)$response->getBody(), true);
            } catch (JsonException $e) {
                throw new TranslateApiException(
                    'Překladové API vrátilo neplatnou odpověď.',
                    $iterator,
                    $totalChunks,
                    $e,
                );
            }

            if (!is_array($response) || !array_key_exists('id', $response)) {
                throw new TranslateApiException(
                    'Překladové API nevrátilo očekávané ID požadavku.',
                    $iterator,
                    $totalChunks,
                );
            }

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

    /**
     * @param LanguageEntity $language
     * @return void
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
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
     * @param bool $onlyMissing vynechat texty, které už jsou přeložené (vyplněné a odlišné od výchozího jazyka)
     * @return void
     */
    private function addPerformanceContentToJson(ActiveRow $contentLanguage, array &$json, ActiveRow $defaultLanguage, bool $onlyMissing = false):void
    {
        /** @var ContentLanguage $contentLanguageModel */
        $contentLanguageModel = $this->container->getByType(ContentLanguage::class);

        $contentLanguageDefault = $contentLanguageModel->getByContentIdAndLanguageId($contentLanguage->content_id, $defaultLanguage->id);
        if($contentLanguageDefault !== null){
            if($contentLanguageDefault->title !== null && !($onlyMissing && $this->isTranslated($contentLanguage->title, $contentLanguageDefault->title))){
                $json['performanceContent_' . $contentLanguage->content_id . '_title'] = $contentLanguageDefault->title;
            }
            if($contentLanguageDefault->description !== null && !($onlyMissing && $this->isTranslated($contentLanguage->description, $contentLanguageDefault->description))){
                $json['performanceContent_' . $contentLanguage->content_id . '_description'] = $contentLanguageDefault->description;
            }
        }
    }

    private function isTranslated(?string $value, string $defaultValue): bool
    {
        return $value !== null && $value !== '' && $value !== $defaultValue;
    }
}
