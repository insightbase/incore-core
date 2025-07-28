<?php

namespace App\UI\Admin\Language;

use App\Component\Log\LogActionEnum;
use App\Component\Log\LogFacade;
use App\Component\Translator\Translator;
use App\Event\EventFacade;
use App\Event\Language\ChangeDefaultEvent;
use App\Model\Admin\Language;
use App\Model\Admin\Translate;
use App\Model\Admin\TranslateLanguage;
use App\Model\Entity\LanguageEntity;
use App\UI\Admin\Language\DataGrid\Exception\DefaultLanguageCannotByDeactivateException;
use App\UI\Admin\Language\Exception\LanguageCallbackIdNotFoundException;
use App\UI\Admin\Language\Exception\LanguageIsDefaultException;
use App\UI\Admin\Language\Exception\LanguageNotFoundException;
use App\UI\Admin\Language\Exception\TranslateInProgressException;
use App\UI\Admin\Language\Form\NewFormData;
use GuzzleHttp\Client;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;
use Nette\Http\Url;
use Nette\Utils\DateTime;
use Nette\Utils\Json;

readonly class LanguageFacade
{
    public function __construct(
        private Language    $languageModel,
        private Translator  $translator,
        private LogFacade   $logFacade,
        private EventFacade $eventFacade,
        private LinkGenerator $linkGenerator,
        private Translate $translateModel,
        private TranslateLanguage $translateLanguageModel,
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
     * @throws TranslateInProgressException
     */
    public function translate(ActiveRow $language):void
    {
        if($language->drop_core_id !== null){
            throw new TranslateInProgressException();
        }

        $defaultLanguage = $this->languageModel->getDefault();

        $json = [];
        foreach($this->translateModel->getNotAdmin() as $translate){
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $defaultLanguage);
            if($translateLanguage !== null) {
                $json[$translate->key] = $translateLanguage->value;
            }
        }

        $callback = $this->linkGenerator->link('Admin:LanguageCallback:translate', ['id' => $language->id]);
        if(array_key_exists('REDIRECT_REMOTE_USER', $_SERVER)){
            $callback = new Url($callback);
            $callback->setUser('insightbase.cz');
            $callback->setPassword('test-insightbase.cz');
            $callback = (string)$callback;
        }

        $body = Json::encode([
            'inputLocale' => $defaultLanguage->url,
            'outputLocale' => $language->url,
            'model' => 'thinking',
            'callback' => $callback,
            'mode' => 'async',
            'value' => $json,
        ]);

        $url = 'https://drop-core.web.app/api/gen/translate';
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
        $language->update([
            'drop_core_id' => $response['id'],
            'drop_core_last_call_date' => new DateTime(),
        ]);
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
        if($language->drop_core_id !== $post['id']){
            throw new LanguageCallbackIdNotFoundException();
        }

        $json = Json::decode($post['value'], true);
        foreach($json as $key => $text){
            $translate = $this->translateModel->getByKey($key);
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
        }
        $language->update([
            'drop_core_id' => null,
        ]);
    }
}
