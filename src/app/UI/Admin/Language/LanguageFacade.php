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
use App\UI\Admin\Language\Form\NewFormData;
use GuzzleHttp\Client;
use Nette\Application\LinkGenerator;
use Nette\Database\Table\ActiveRow;
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
     */
    public function translate(ActiveRow $language):void
    {
        $defaultLanguage = $this->languageModel->getDefault();

        $json = [];
        foreach($this->translateModel->getNotAdmin() as $translate){
            $translateLanguage = $this->translateLanguageModel->getByTranslateAndLanguage($translate, $defaultLanguage);
            if($translateLanguage !== null) {
                $json[$translate->key] = $translateLanguage->value;
            }
        }

        $body = Json::encode([
            'inputLocale' => $defaultLanguage->url,
            'outputLocale' => $language->url,
            'model' => 'thinking',
//                'callback' => $this->linkGenerator->link('Admin:LanguageCallback:translate', ['id' => $language->id]),
//            'callback' => null,
            'callback' => 'https://ad83-178-255-168-143.ngrok-free.app/admin/language-callback/translate/2',
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
        dumpe((string)$response->getBody());
    }
}
