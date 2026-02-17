<?php

namespace Steeve\MonCashLaravel\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Steeve\MonCashLaravel\Sdk\Exception\MonCashException;
use Steeve\MonCashLaravel\Sdk\Exception\MonCashTransferException;

class MonCashBusiness
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
     * @throws MonCashTransferException
     * @throws MonCashException
     */
    public function transfert(string $receiver, float $amount, string $desc, string $reference): array
    {
        try {
            $token = $this->auth->getToken();

            // POST /v1/Transfert ('Transfert' stated in prompt with 't')
            $response = $this->client->request('POST', $this->config->getBaseApiUrl() . '/v1/Transfert', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'receiver'  => $receiver,
                    'amount'    => $amount,
                    'desc'      => $desc,
                    // Assuming 'reference' is passed as a key too, sometimes it might be 'client_reference' or similar. 
                    // Sticking to prompt.
                    'reference' => $reference,
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            // Handle insufficient funds or other API errors
            throw new MonCashTransferException("MonCash Transfert Error: " . $e->getMessage() . " | Body: " . $responseBody, $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new MonCashTransferException("MonCash Transfert Network Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MonCashTransferException
     * @throws MonCashException
     */
    public function prefundedTransactionStatus(string $transferId): array
    {
        try {
            $token = $this->auth->getToken();

            $response = $this->client->request('POST', $this->config->getBaseApiUrl() . '/v1/PrefundedTransactionStatus', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'transferId' => $transferId, // Assuming key is transferId or similar
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw new MonCashTransferException("MonCash TransferStatus Error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new MonCashTransferException("MonCash TransferStatus Network Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MonCashTransferException
     * @throws MonCashException
     */
    public function prefundedBalance(): array
    {
        try {
            $token = $this->auth->getToken();

            // GET /v1/PrefundedBalance
            $response = $this->client->request('GET', $this->config->getBaseApiUrl() . '/v1/PrefundedBalance', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            throw new MonCashTransferException("MonCash Balance Error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new MonCashTransferException("MonCash Balance Network Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
