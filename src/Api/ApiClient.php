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
                'base_url'       => $this->getBaseUrl(),
                'endpoint'       => 'orders',
                'api_token'      => $this->getApiToken(),
                'method'         => 'POST',
                'plugin_version' => $this->getPluginVersion(),
                'body'           => $orderData
            ]
        );
    }

    public function confirmOrder(string $orderUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'base_url'       => $this->getBaseUrl(),
                'endpoint'       => 'orders/' . $orderUuid . '/confirm',
                'api_token'      => $this->getApiToken(),
                'method'         => 'POST',
                'plugin_version' => $this->getPluginVersion(),
                'body'           => $body
            ]
        );
    }

    public function getOrder(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'base_url' => $this->getBaseUrl(),
                'endpoint' => 'orders/' . $orderUuid,
                'api_token' => $this->getApiToken(),
                'method' => 'GET',
                'plugin_version' => $this->getPluginVersion(),
            ]
        );
    }

    private function apiCall(array $params): array
    {
        return $this->libraryCallContract->call('Mondu::guzzle_connector', $params);
    }

    private function getBaseUrl(): string
    {
        return $this->settings->getApiUrl();
    }

    private function getApiToken(): string
    {
        return $this->settings->getSetting('apiToken');
    }

    private function getPluginVersion(): string
    {
        return $this->settings->getPluginVersion();
    }
}
