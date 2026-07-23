<?php

namespace App\Component\Credit;

use App\Component\DropCore\DropCoreConfig;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Nette\Utils\Json;

class CreditClient
{
    private const float TIMEOUT = 5.0;

    private ClientInterface $httpClient;

    public function __construct(?ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ?? new Client();
    }

    /**
     * Zůstatek kreditů účtu. Při jakékoli chybě API vrací null — indikátor se prostě nezobrazí.
     */
    public function getBalance(DropCoreConfig $config, string $account): ?int
    {
        try {
            $response = $this->httpClient->request('POST', $config->apiUrl.'/promo/credits/value', [
                'headers' => [
                    'identity-token' => $config->identityToken,
                    'store' => $config->store,
                    'content-type' => 'application/json',
                ],
                'body' => Json::encode(['id' => $account]),
                'timeout' => self::TIMEOUT,
                'http_errors' => true,
            ]);
            $data = Json::decode((string) $response->getBody(), forceArrays: true);
        } catch (\Throwable) {
            return null;
        }

        if (!is_array($data) || !array_key_exists('value', $data) || !is_numeric($data['value'])) {
            return null;
        }

        return (int) $data['value'];
    }
}
