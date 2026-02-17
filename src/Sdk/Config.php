<?php

namespace Steeve\MonCashLaravel\Sdk;

class Config
{
    public const MODE_SANDBOX = 'sandbox';
    public const MODE_LIVE = 'live';

    private string $mode;
    private string $clientId;
    private string $clientSecret;
    private int $timeout;
    private int $tokenLifetime;

    private array $apiUrls = [
        self::MODE_SANDBOX => 'https://sandbox.moncashbutton.digicelgroup.com/Api',
        self::MODE_LIVE => 'https://moncashbutton.digicelgroup.com/Api',
    ];

    private array $gatewayUrls = [
        self::MODE_SANDBOX => 'https://sandbox.moncashbutton.digicelgroup.com/Moncash-middleware',
        self::MODE_LIVE => 'https://moncashbutton.digicelgroup.com/Moncash-middleware',
    ];

    public function __construct(string $mode, string $clientId, string $clientSecret, int $timeout = 60, int $tokenLifetime = 50)
    {
        $this->mode = $mode;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->timeout = $timeout;
        $this->tokenLifetime = $tokenLifetime;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getClientSecret(): string
    {
        return $this->clientSecret;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getTokenLifetime(): int
    {
        return $this->tokenLifetime;
    }

    public function getBaseApiUrl(): string
    {
        return $this->apiUrls[$this->mode] ?? $this->apiUrls[self::MODE_SANDBOX];
    }

    public function getBaseGatewayUrl(): string
    {
        return $this->gatewayUrls[$this->mode] ?? $this->gatewayUrls[self::MODE_SANDBOX];
    }

    public function isSandbox(): bool
    {
        return $this->mode === self::MODE_SANDBOX;
    }
}
