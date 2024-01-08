<?php

namespace Mondu\Api;

use Mondu\Services\SettingsService;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Log\Loggable;

class ApiClient
{
    use Loggable;
    /**
     * @var LibraryCallContract
     */
    private $libraryCallContract;

    /**
     * @var SettingsService
     */
    private $settings;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var string
     */
    private $apiUrl;

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

    public function createInvoice(string $orderUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'orders/' . $orderUuid . '/invoices',
                'method'   => 'POST',
                'body'     => $body
            ]
        );
    }

    public function createCreditNote(string $invoiceUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'invoices/' . $invoiceUuid . '/credit_notes',
                'method'   => 'POST',
                'body'     => $body
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

    public function getInvoices(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'orders/' . $orderUuid . '/invoices',
                'method' => 'GET',
            ]
        );
    }

    public function getPaymentMethods(): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'payment_methods',
                'method' => 'GET',
            ]
        );
    }

    public function setApiKey(string $apiKey): ApiClient
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function setApiUrl(string $apiUrl): ApiClient
    {
        $this->apiUrl = $apiUrl;
        return $this;
    }

    private function apiCall(array $params): array
    {
        $this->getLogger(__CLASS__ . '::' . __FUNCTION__)
            ->info('Mondu::Logs.apiCall', $params);

        $response = $this->libraryCallContract->call('Mondu::guzzle_connector', array_merge(
            $this->getDefaultParams(),
            $params
        ));

        if (isset($response['error'])) {
            $this->getLogger(__CLASS__ . '::' . __FUNCTION__)
                ->error('Mondu::Logs.apiError', $response);
        }
        return $response;
    }

    private function getDefaultParams(): array
    {
        return [
            'base_url' => $this->getBaseUrl(),
            'api_token' => $this->getApiKey(),
            'plugin_version' => $this->getPluginVersion(),
            'plugin_name' => $this->getPluginName(),
        ];
    }

    private function getBaseUrl(): string
    {
        return $this->apiUrl ?? $this->settings->getApiUrl();
    }

    private function getApiKey(): string
    {
        return $this->apiKey ?? $this->settings->getSetting('apiKey');
    }

    private function getPluginVersion(): string
    {
        return $this->settings->getPluginVersion();
    }

    private function getPluginName(): string
    {
        return $this->settings->getPluginName();
    }
}
