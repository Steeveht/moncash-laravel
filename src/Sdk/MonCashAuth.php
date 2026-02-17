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

            // Use API expires_in or fallback to config lifetime
            $expiresIn = isset($body['expires_in']) ? (int)$body['expires_in'] : 3600;
            $lifetime = min($expiresIn, $this->config->getTokenLifetime());

            // Subtract a small buffer (5s) to refresh slightly before local expiration
            $this->expiresAt = time() + $lifetime - 5;

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
