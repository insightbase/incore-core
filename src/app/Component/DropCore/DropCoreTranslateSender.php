<?php

namespace App\Component\DropCore;

use App\UI\Accessory\ParameterBag;
use App\UI\Admin\Language\Exception\NotEnoughCreditsException;
use App\UI\Admin\Language\Exception\TranslateApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\FileSystem;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

/**
 * Odešle uloženou dávku do DropCore (/gen/translate), v prostředí „simulation“ do DropCoreSimulator.
 */
final readonly class DropCoreTranslateSender implements TranslateRequestSender
{
    public function __construct(
        private DropCoreConfigProvider $dropCoreConfigProvider,
        private DropCoreSimulator $dropCoreSimulator,
        private ParameterBag $parameterBag,
    ) {}

    public function send(string $request, int $languageId, int $batchIndex, int $totalBatches): string
    {
        $dropCoreConfig = $this->dropCoreConfigProvider->getConfig();
        if (null === $dropCoreConfig) {
            throw new TranslateApiException(
                'Pro překlad je potřeba v nastavení vyplnit identity token a prostředí (DropCore).',
                $batchIndex,
                $totalBatches,
            );
        }

        if ($dropCoreConfig->simulation) {
            try {
                $body = Json::decode($request, true);
            } catch (JsonException $e) {
                throw new TranslateApiException('Uložený požadavek na překlad není platný JSON.', $batchIndex, $totalBatches, $e);
            }
            $dropCoreId = $this->dropCoreSimulator->enqueue($body['value'], $languageId, $body['outputLocale'], $batchIndex, $totalBatches);
        } else {
            $dropCoreId = $this->requestTranslation($dropCoreConfig, $dropCoreConfig->apiUrl . '/gen/translate', $request, $batchIndex, $totalBatches);
        }

        FileSystem::write(
            $this->parameterBag->tempDir . '/language_api_' . time() . '_' . $batchIndex . '_sent',
            Json::encode(['drop_core_id' => $dropCoreId, 'request' => $request]),
        );

        return $dropCoreId;
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
            // Timeouty musí být kratší než zámek dávky (LanguageTranslate::LOCK_TIMEOUT, 2 min),
            // jinak by ji po vypršení zámku mohl odeslat i jiný request.
            $client = new Client(['timeout' => 60, 'connect_timeout' => 10]);
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
            if ($e instanceof BadResponseException && 402 === $e->getResponse()->getStatusCode()) {
                throw new NotEnoughCreditsException('Na překlad není dostatek kreditů.', $iterator, $totalChunks, $e);
            }

            throw new TranslateApiException('Volání překladového API selhalo: ' . $e->getMessage(), $iterator, $totalChunks, $e);
        }

        try {
            $response = Json::decode((string) $response->getBody(), true);
        } catch (JsonException $e) {
            throw new TranslateApiException('Překladové API vrátilo neplatnou odpověď.', $iterator, $totalChunks, $e);
        }

        if (!is_array($response) || !array_key_exists('id', $response)) {
            throw new TranslateApiException('Překladové API nevrátilo očekávané ID požadavku.', $iterator, $totalChunks);
        }

        return (string) $response['id'];
    }
}
