<?php

namespace Steeve\MonCashLaravel\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Steeve\MonCashLaravel\Sdk\Exception\MonCashException;

class MonCashAuth
{
    private Config $config;
    private ClientInterface $client;

    private ?string $accessToken = null;
    private ?int $expiresAt = null;

    public function __construct(Config $config, ClientInterface $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * @throws MonCashException
     */
    public function getToken(): string
    {
        if ($this->isValidToken()) {
            return $this->accessToken;
        }

        try {
            $authString = base64_encode($this->config->getClientId() . ':' . $this->config->getClientSecret());

            $response = $this->client->request('POST', $this->config->getBaseApiUrl() . '/oauth/token', [
                'headers' => [
                    'Authorization' => 'Basic ' . $authString,
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                    'Accept'        => 'application/json',
                ],
                'form_params' => [
                    'scope'      => 'read,write',
                    'grant_type' => 'client_credentials',
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['access_token'])) {
                throw new MonCashException("Invalid response from MonCash Auth: Access token not found.");
            }

            $this->accessToken = $body['access_token'];
            // Default to 50 seconds if expires_in is missing, or use the provided value. 
            // The prompt mentioned "Par défaut 50s pour être sûr" in config, adhering to that margin.
            $expiresIn = isset($body['expires_in']) ? (int)$body['expires_in'] : 3600;

            // Subtract a buffer (e.g. 60s) to refresh before actual expiration
            $this->expiresAt = time() + $expiresIn - 60;

            return $this->accessToken;
        } catch (GuzzleException $e) {
            throw new MonCashException("MonCash Auth Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function isValidToken(): bool
    {
        return $this->accessToken !== null && $this->expiresAt !== null && time() < $this->expiresAt;
    }
}
