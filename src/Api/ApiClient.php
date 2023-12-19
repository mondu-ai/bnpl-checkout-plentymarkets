<?php

namespace Mondu\Api;

use Mondu\Services\SettingsService;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;

class ApiClient
{
    /**
     * @var LibraryCallContract
     */
    private $libraryCallContract;

    /**
     * @var SettingsService
     */
    private $settings;

    public function __construct(
        LibraryCallContract $libraryCallContract,
        SettingsService $settingsService
    ) {
        $this->libraryCallContract = $libraryCallContract;
        $this->settings = $settingsService;
    }

    public function createOrder(array $orderData): array
    {
        return $this->apiCall(
            [
                'endpoint'       => 'orders',
                'method'         => 'POST',
                'body'           => $orderData
            ]
        );
    }

    public function confirmOrder(string $orderUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'endpoint'       => 'orders/' . $orderUuid . '/confirm',
                'method'         => 'POST',
                'body'           => $body
            ]
        );
    }

    public function cancelOrder(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'endpoint'       => 'orders/' . $orderUuid . '/cancel',
                'method' => 'POST',
                'body' => []
            ]
        );
    }

    public function getWebhookSecret(): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'webhooks/keys',
                'method' => 'GET',
                'body' => []
            ]
        );
    }

    public function registerWebhooks($body): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'webhooks',
                'method' => 'POST',
                'body' => $body
            ]
        );
    }

    public function getOrder(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'orders/' . $orderUuid,
                'method' => 'GET',
            ]
        );
    }

    private function apiCall(array $params): array
    {
        return $this->libraryCallContract->call('Mondu::guzzle_connector', array_merge(
            $this->getDefaultParams(),
            $params
        ));
    }

    private function getDefaultParams(): array
    {
        return [
            'base_url' => $this->getBaseUrl(),
            'api_token' => $this->getApiKey(),
            'plugin_version' => $this->getPluginVersion(),
        ];
    }

    private function getBaseUrl(): string
    {
        return $this->settings->getApiUrl();
    }

    private function getApiKey(): string
    {
        return $this->settings->getSetting('apiKey');
    }

    private function getPluginVersion(): string
    {
        return $this->settings->getPluginVersion();
    }
}
