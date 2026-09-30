<?php

namespace App\UI\Admin\Language;

use App\Component\DropCore\DropCoreConfig;
use App\Component\DropCore\DropCoreConfigProvider;
use App\Component\DropCore\DropCoreSimulator;
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
use App\Component\Translation\LabeledTranslationProvider;
use App\Component\Translation\TranslationJobFacade;
use App\Component\Translation\TranslationSendResult;
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
    /** Callbacky DropCore, které dorazily dřív, než se uložil jejich záznam v language_translate. */
    private const string EARLY_CALLBACK_CACHE_NAMESPACE = 'dropCoreEarlyCallback';

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
        private readonly \App\Model\Admin\Translate $translateModel,
        private readonly TranslationJobFacade $translationJobFacade,
        private readonly DropCoreSimulator $dropCoreSimulator,
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
     * @return TranslationSendResult počet odeslaných textů a ID dávek
     * @throws BasicAuthNotSetException
     * @throws TranslateInProgressException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translate(ActiveRow $language):TranslationSendResult
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

        $sent = [];
        try {
            if ($json !== []) {
                $this->sendJsonToTranslate($json, $defaultLanguage, $language, 'bulk', $sent);
            }
        } finally {
            $this->startJob(
                fn(): string => $this->translator->translate('translationJob_language%language%', ['language' => $language->name]),
                $sent,
                count($json),
            );
        }

        return new TranslationSendResult(count($json), $sent);
    }

    /**
     * Přeloží jedinou položku zdroje registrovaného přes TranslationProvider do jednoho jazyka.
     * Obálka nad translateProviderItems() pro čitelnost volání z presenterů.
     *
     * @param LanguageEntity $language
     * @param ?string $jobLabel popisek v liště s průběhem; null = odvodí se ze zdroje a jazyka
     * @return list<string> DropCore ID odeslaných dávek
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translateProviderItem(string $systemName, int $id, ActiveRow $language, ?string $jobLabel = null): array
    {
        return $this->translateProviderItems($systemName, [$id], $language, $jobLabel);
    }

    /**
     * Přeloží vyjmenované položky jednoho zdroje do jednoho jazyka. Všechny odejdou
     * v jedné dávce, takže překlad stovky položek nestojí sto samostatných volání API.
     * Průběh se sám zobrazí v liště překladů (TranslationJobFacade).
     *
     * @param int[] $ids
     * @param LanguageEntity $language
     * @param ?string $jobLabel popisek v liště s průběhem; null = odvodí se ze zdroje a jazyka
     * @return list<string> DropCore ID odeslaných dávek (prázdné, když nebylo co posílat)
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translateProviderItems(string $systemName, array $ids, ActiveRow $language, ?string $jobLabel = null): array
    {
        $sent = [];
        try {
            $this->sendProviderItems($systemName, $ids, $language, $sent);
        } finally {
            $this->startJob(
                fn(): string => $jobLabel ?? $this->translator->translate('translationJob_provider%source%%language%', [
                    'source' => $this->getSourceLabel($systemName),
                    'language' => $language->name,
                ]),
                $sent,
                self::jobItemCount($ids),
            );
        }

        return $sent;
    }

    /**
     * Přeloží vyjmenované položky jednoho zdroje do všech jazyků k překladu
     * (tlačítka „Přeložit“ u detailu entity). V liště s průběhem je to jedna
     * společná úloha - i když se část dávek odeslat nepodaří, sleduje se aspoň to,
     * co odešlo.
     *
     * @param int[] $ids
     * @param ?string $jobLabel popisek v liště s průběhem; null = odvodí se ze zdroje
     * @return list<string> DropCore ID odeslaných dávek
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translateProviderItemsToAllLanguages(string $systemName, array $ids, ?string $jobLabel = null): array
    {
        $sent = [];
        try {
            foreach ($this->languageModel->getToTranslateNotDefault() as $language) {
                $this->sendProviderItems($systemName, $ids, $language, $sent);
            }
        } finally {
            $this->startJob(
                fn(): string => $jobLabel ?? $this->translator->translate('translationJob_provider%source%', [
                    'source' => $this->getSourceLabel($systemName),
                ]),
                $sent,
                self::jobItemCount($ids),
            );
        }

        return $sent;
    }

    /**
     * @param int[] $ids
     * @param LanguageEntity $language
     * @param list<string> $sentDropCoreIds sem se přidají ID odeslaných dávek
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    private function sendProviderItems(string $systemName, array $ids, ActiveRow $language, array &$sentDropCoreIds): void
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

        $this->sendJsonToTranslate($json, $defaultLanguage, $language, $systemName, $sentDropCoreIds);
    }

    /**
     * Popisek zdroje pro lištu s průběhem: LabeledTranslationProvider::getLabel(), jinak systemName.
     */
    private function getSourceLabel(string $systemName): string
    {
        $provider = $this->translationProviderRegistry->get($systemName);

        return $provider instanceof LabeledTranslationProvider
            ? $this->translator->translate($provider->getLabel())
            : $systemName;
    }

    /**
     * Založí úlohu v liště s průběhem. Popisek se sestaví až tady - mimo přihlášení
     * (CLI, ověřovací skripty) nebo bez odeslaných dávek se nic nezakládá a Translator
     * by tam ani neměl nastavený jazyk.
     *
     * @param \Closure(): string $label
     * @param list<string> $dropCoreIds
     */
    private function startJob(\Closure $label, array $dropCoreIds, int $itemCount): void
    {
        if ($dropCoreIds === [] || !$this->userSecurity->isLoggedIn()) {
            return;
        }

        $this->translationJobFacade->start($label(), $dropCoreIds, $itemCount);
    }

    /**
     * Souhrn „Hotovo (N)“ má smysl jen u překladu více položek najednou.
     *
     * @param int[] $ids
     */
    private static function jobItemCount(array $ids): int
    {
        return count($ids) > 1 ? count($ids) : 0;
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
        if ($languageTranslate !== null) {
            $languageTranslate->update(['finished' => new DateTime()]);
        } else {
            // Záznam ještě neexistuje (callback předběhl uložení) - dokončení si poznamenáme,
            // sendJsonToTranslate() ho po uložení záznamu převezme. Jinak by průběh překladu nikdy nedoběhl.
            (new Cache($this->storage, self::EARLY_CALLBACK_CACHE_NAMESPACE))
                ->save((string) $post['id'], new DateTime(), [Cache::Expire => '1 day']);
        }

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
     * @param string $trigger odkud byl překlad spuštěn (bulk = hromadně za jazyk, jméno zdroje, performance), jen pro metadata
     * @param list<string> $sentDropCoreIds sem se průběžně přidávají ID odeslaných dávek, takže
     *                                      při chybě v půlce v něm zůstane to, co už odešlo
     * @return void
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    private function sendJsonToTranslate(array $json, ActiveRow $defaultLanguage, ActiveRow $language, string $trigger, array &$sentDropCoreIds):void
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
                'metadata' => $this->buildMetadata($shortJson, $trigger, $defaultLanguage, $language, $iterator, $totalChunks),
                'value' => $shortJson,
            ]);

            $url = $dropCoreConfig->apiUrl . '/gen/translate';

            FileSystem::write($tempFile . '_' . $iterator, Json::encode([
                'callback' => $callback,
                'url' => $url,
                'body' => $bodyArray,
            ]));

            // Prostředí „simulation“ (jen debug režim): dávku místo DropCore „přeloží“ DropCoreSimulator.
            $dropCoreId = $dropCoreConfig->simulation
                ? $this->dropCoreSimulator->enqueue($shortJson, $language->id, $language->url, $iterator, $totalChunks)
                : $this->requestTranslation($dropCoreConfig, $url, $body, $iterator, $totalChunks);

            $sentDropCoreIds[] = $dropCoreId;
            $earlyCallbackCache = new Cache($this->storage, self::EARLY_CALLBACK_CACHE_NAMESPACE);
            $earlyFinished = $earlyCallbackCache->load($dropCoreId);
            $this->languageTranslateModel->insert([
                'drop_core_id' => $dropCoreId,
                'user_id' => $this->userSecurity->getId(),
                'language_id' => $language->id,
                'datetime' => new DateTime(),
                'finished' => $earlyFinished instanceof \DateTimeInterface ? $earlyFinished : null,
                'request' => $body,
            ]);
            if ($earlyFinished !== null) {
                $earlyCallbackCache->remove($dropCoreId);
            }

            FileSystem::write($tempFile . '_' . $iterator, Json::encode([
                'drop_core_id' => $dropCoreId,
                'callback' => $callback,
                'url' => $url,
                'body' => $bodyArray,
            ]));

            $iterator++;
        }
    }

    /**
     * Odešle jednu dávku do DropCore a vrátí ID úlohy, které DropCore přidělil.
     *
     * @throws NotEnoughCreditsException
     * @throws TranslateApiException
     */
    private function requestTranslation(DropCoreConfig $dropCoreConfig, string $url, string $body, int $iterator, int $totalChunks): string
    {
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

        return (string) $response['id'];
    }

    /**
     * @param LanguageEntity $language
     * @return TranslationSendResult počet odeslaných textů a ID dávek
     * @throws BasicAuthNotSetException
     * @throws TranslateApiException
     * @throws InvalidLinkException
     * @throws JsonException
     */
    public function translatePerformancesContent(ActiveRow $language):TranslationSendResult
    {
        /** @var ContentLanguage $contentLanguageModel */
        $contentLanguageModel = $this->container->getByType(ContentLanguage::class);

        $defaultLanguage = $this->languageModel->getDefault();
        $json = [];

        foreach($contentLanguageModel->getByLanguage($language) as $contentLanguage) {
            $this->addPerformanceContentToJson($contentLanguage, $json, $defaultLanguage, true);
        }

        $sent = [];
        try {
            if ($json !== []) {
                $this->sendJsonToTranslate($json, $defaultLanguage, $language, 'performance', $sent);
            }
        } finally {
            $this->startJob(
                fn(): string => $this->translator->translate('translationJob_performance%language%', ['language' => $language->name]),
                $sent,
                count($json),
            );
        }

        return new TranslationSendResult(count($json), $sent);
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

    /**
     * Popis dávky pro DropCore, aby bylo v jeho logu vidět, co se překládalo.
     * DropCore metadata nijak nezpracovává, jen je uloží k úloze - proto jsou
     * čitelná česky pro člověka, ne pro stroj.
     *
     * @param array<string, mixed> $chunk
     * @param LanguageEntity $defaultLanguage
     * @param LanguageEntity $language
     * @return array<string, mixed>
     */
    private function buildMetadata(array $chunk, string $trigger, ActiveRow $defaultLanguage, ActiveRow $language, int $iterator, int $totalChunks): array
    {
        /** @var array<string, array<int, true>> $sources název zdroje => id položek */
        $sources = [];
        /** @var array<string, int> $textCounts název zdroje => počet textů */
        $textCounts = [];
        foreach (array_keys($chunk) as $key) {
            $translationKey = \App\Component\Translation\TranslationKey::tryDecode((string) $key);
            if ($translationKey !== null) {
                $systemName = $translationKey->systemName;
                $id = $translationKey->id;
            } else {
                // Starý formát klíče `typ_id_pole` (zatím jen performanceContent).
                $parts = explode('_', (string) $key, 3);
                $systemName = $parts[0];
                $id = (int) ($parts[1] ?? 0);
            }
            $sources[$systemName][$id] = true;
            $textCounts[$systemName] = ($textCounts[$systemName] ?? 0) + 1;
        }

        $content = [];
        foreach ($sources as $systemName => $ids) {
            $ids = array_keys($ids);
            $description = self::czechCount($textCounts[$systemName], 'text', 'texty', 'textů');

            // U slovníku UI textů je čitelnější název klíče než ID řádku.
            if ($systemName === 'translate') {
                $keys = $this->translateModel->getTable()->where('id', $ids)->fetchPairs('id', 'key');
                $description .= ', klíče: ' . implode(', ', $keys);
            } else {
                $description .= ', položky (ID): ' . implode(', ', $ids);
            }

            $content[$this->getMetadataSourceLabel($systemName)] = $description;
        }

        // Identita nese řádek uživatele z Authenticatoru; mimo přihlášení (CLI) je null.
        $identity = $this->userSecurity->getIdentity();
        $userName = $identity !== null
            ? trim(($identity->firstname ?? '') . ' ' . ($identity->lastname ?? ''))
            : '';
        $userId = $this->userSecurity->getId();

        return [
            'aplikace' => 'inCore',
            'web' => (new Url($this->linkGenerator->link('Admin:Home:default')))->getHost(),
            'překlad' => match ($trigger) {
                'bulk' => 'Hromadný překlad celého jazyka',
                'performance' => 'Překlad obsahu performance',
                default => 'Překlad vybraných položek: ' . $this->getMetadataSourceLabel($trigger),
            },
            'jazyk' => $defaultLanguage->name . ' → ' . $language->name,
            'dávka' => ($iterator + 1) . ' z ' . $totalChunks,
            'spustil' => $userId === null
                ? 'systém (bez přihlášení)'
                : ($userName !== '' ? $userName : 'uživatel') . ' (ID ' . $userId . ')',
            'obsah' => $content,
        ];
    }

    /**
     * Název zdroje pro metadata. Překlad popisku se nesmí pokazit odeslání dávky
     * (mimo administraci nemusí mít Translator nastavený jazyk), proto záloha na systemName.
     */
    private function getMetadataSourceLabel(string $systemName): string
    {
        if ($systemName === 'performanceContent') {
            return 'Obsah performance';
        }

        try {
            return $this->getSourceLabel($systemName);
        } catch (\Throwable) {
            return $systemName;
        }
    }

    /**
     * „1 text“, „3 texty“, „5 textů“.
     */
    private static function czechCount(int $count, string $one, string $few, string $many): string
    {
        $word = match (true) {
            $count === 1 => $one,
            $count >= 2 && $count <= 4 => $few,
            default => $many,
        };

        return $count . ' ' . $word;
    }

    private function isTranslated(?string $value, string $defaultValue): bool
    {
        return $value !== null && $value !== '' && $value !== $defaultValue;
    }
}
