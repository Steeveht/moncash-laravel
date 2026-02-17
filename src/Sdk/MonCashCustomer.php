<?php

namespace Steeve\MonCashLaravel\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Steeve\MonCashLaravel\Sdk\Exception\MonCashException;

class MonCashCustomer
{
    private Config $config;
    private MonCashAuth $auth;
    private ClientInterface $client;

    public function __construct(Config $config, MonCashAuth $auth, ClientInterface $client)
    {
        $this->config = $config;
        $this->auth = $auth;
        $this->client = $client;
    }

    /**
     * @throws MonCashException
     */
    public function customerStatus(string $phone): array
    {
        try {
            $token = $this->auth->getToken();

            // POST /v1/CustomerStatus
            $response = $this->client->request('POST', $this->config->getBaseApiUrl() . '/v1/CustomerStatus', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'account' => $phone,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            // If 404, maybe account doesn't exist? Or just return the error structure.
            // Prompt says "Retourne booléen ou statut indiquant si le compte existe et est actif".
            // We return the array, caller interprets.
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            throw new MonCashException("MonCash CustomerStatus Error: " . $e->getMessage() . " | Body: " . $responseBody, $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new MonCashException("MonCash CustomerStatus Network Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
