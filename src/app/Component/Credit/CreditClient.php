<?php

namespace App\Component\Credit;

use App\Component\DropCore\DropCoreConfig;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Utils\Json;
use Nette\Utils\JsonException;

class CreditClient
{
    private const float TIMEOUT = 5.0;

    private ClientInterface $httpClient;

    public function __construct(
        private readonly DropCoreConfig $config,
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Zůstatek kreditů účtu. Při jakékoli chybě API vrací null — indikátor se prostě nezobrazí.
     */
    public function getBalance(string $account): ?int
    {
        try {
            $response = $this->httpClient->request('POST', $this->config->apiUrl.'/promo/credits/value', [
                'headers' => [
                    'access-token' => $this->config->accessToken,
                    'store' => $this->config->store,
                    'content-type' => 'application/json',
                ],
                'body' => Json::encode(['id' => $account]),
                'timeout' => self::TIMEOUT,
                'http_errors' => true,
            ]);
            $data = Json::decode((string) $response->getBody(), forceArrays: true);
        } catch (GuzzleException|JsonException) {
            return null;
        }

        if (!is_array($data) || !array_key_exists('value', $data) || !is_numeric($data['value'])) {
            return null;
        }

        return (int) $data['value'];
    }
}
