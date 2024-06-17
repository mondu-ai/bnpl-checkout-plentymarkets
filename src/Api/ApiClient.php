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
            ],
            'CREATE_ORDER'
        );
    }

    public function confirmOrder(string $orderUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'endpoint'       => 'orders/' . $orderUuid . '/confirm',
                'method'         => 'POST',
                'body'           => $body
            ],
            'CONFIRM_ORDER'
        );
    }

    public function cancelOrder(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'endpoint'       => 'orders/' . $orderUuid . '/cancel',
                'method' => 'POST',
                'body' => []
            ],
            'CANCEL_ORDER'
        );
    }

    public function createInvoice(string $orderUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'orders/' . $orderUuid . '/invoices',
                'method'   => 'POST',
                'body'     => $body
            ],
            'CREATE_INVOICE'
        );
    }

    public function createCreditNote(string $invoiceUuid, array $body = []): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'invoices/' . $invoiceUuid . '/credit_notes',
                'method'   => 'POST',
                'body'     => $body
            ],
            'CREATE_CREDIT_NOTE'
        );
    }

    public function getWebhookSecret(): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'webhooks/keys',
                'method' => 'GET'
            ],
            'GET_WEBHOOK_SECRET'
        );
    }

    public function registerWebhooks($body): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'webhooks',
                'method' => 'POST',
                'body' => $body
            ],
            'REGISTER_WEBHOOKS'
        );
    }

    public function getOrder(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'orders/' . $orderUuid,
                'method' => 'GET',
            ],
            'GET_ORDER'
        );
    }

    public function getInvoices(string $orderUuid): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'orders/' . $orderUuid . '/invoices',
                'method' => 'GET',
            ],
            'GET_INVOICES'
        );
    }

    public function getPaymentMethods(): array
    {
        return $this->apiCall(
            [
                'endpoint' => 'payment_methods',
                'method' => 'GET',
            ],
            'GET_PAYMENT_METHODS'
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

    private function apiCall(array $params, string $originEvent): array
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

            $this->sendErrorEvent($params, $response, $originEvent);
        }
        return $response;
    }

    private function sendErrorEvent(array $request, array $response, string $originEvent)
    {
        $body = [
            'plugin' => $this->getPluginName(),
            'version' => $this->getPluginVersion(),
            'response_status' => (string) $response['error_no'],
            'request_body' => $request['body'] ?: null,
            'origin_event' => $originEvent,
            'error_trace' => $response['error_file'],
            'error_message' => $response['error_msg']
        ];

        $this->libraryCallContract->call('Mondu::guzzle_connector', array_merge(
            $this->getDefaultParams(),
            [
                'body' => $body,
                'endpoint' => 'plugin/events',
                'method' => 'POST'
            ]
        ));
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
