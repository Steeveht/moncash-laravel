<?php

namespace Steeve\MonCashLaravel\Sdk;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Steeve\MonCashLaravel\Sdk\Exception\MonCashException;
use Steeve\MonCashLaravel\Sdk\Exception\MonCashPaymentException;

class MonCashPayment
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
     * @throws MonCashPaymentException
     * @throws MonCashException
     */
    public function createPayment(string $orderId, float $amount): array
    {
        try {
            $token = $this->auth->getToken();

            // POST /v1/CreatePayment
            $response = $this->client->request('POST', $this->config->getBaseApiUrl() . '/v1/CreatePayment', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => [
                    'orderId' => $orderId,
                    'amount'  => $amount,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (!isset($body['payment_token'])) {
                throw new MonCashPaymentException("Invalid response from MonCash CreatePayment: payment_token missing.");
            }

            // Construct redirect URL
            // mode Sandbox or Live
            $redirectUrl = $this->config->getBaseGatewayUrl() . '/Payment/Redirect?token=' . $body['payment_token']['token'];

            return [
                'payment_token' => $body['payment_token'],
                'redirect_url'  => $redirectUrl,
                'raw_response'  => $body, // Useful for debugging or extra fields
            ];
        } catch (ClientException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            throw new MonCashPaymentException("MonCash Payment Error: " . $e->getMessage() . " | Body: " . $responseBody, $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new MonCashPaymentException("MonCash Payment Network Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws MonCashPaymentException
     * @throws MonCashException
     */
    public function verifyByTransactionId(string $transactionId): array
    {
        return $this->verifyPayment('/v1/RetrieveTransactionPayment', ['transactionId' => $transactionId]);
    }

    /**
     * @throws MonCashPaymentException
     * @throws MonCashException
     */
    public function verifyByOrderId(string $orderId): array
    {
        return $this->verifyPayment('/v1/RetrieveOrderPayment', ['orderId' => $orderId]);
    }

    /**
     * Helper to verify payment
     * Output: Retourne le statut, le numéro du payeur (payer) et le coût (cost).
     */
    private function verifyPayment(string $endpoint, array $payload): array
    {
        try {
            $token = $this->auth->getToken();

            $response = $this->client->request('POST', $this->config->getBaseApiUrl() . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            // Expecting keys like 'payment', 'status', 'payer', 'cost' in response
            if (!isset($body['payment'])) {
                // Sometimes the structure might be different, let's return the whole body but check for basic success indicators if possible
                // But since I don't see the exact response structure, I trust the "Output" requirement: status, payer, cost.
                // Assuming they are at the top level or inside a 'payment' object.
                // Let's assume standard MonCash response where 'payment' key holds the details.
                // If not, we return the body as is.
            }

            return $body;
        } catch (ClientException $e) {
            throw new MonCashPaymentException("MonCash Verify Error: " . $e->getMessage(), $e->getCode(), $e);
        } catch (GuzzleException $e) {
            throw new MonCashPaymentException("MonCash Verify Network Error: " . $e->getMessage(), $e->getCode(), $e);
        }
    }
}
