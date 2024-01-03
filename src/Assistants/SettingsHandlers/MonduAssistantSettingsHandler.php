<?php
namespace Mondu\Assistants\SettingsHandlers;

use Mondu\Api\ApiClient;
use Mondu\Helper\DomainHelper;
use Mondu\Services\SettingsService;
use Plenty\Modules\Plugin\Contracts\PluginLayoutContainerRepositoryContract;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;
use Plenty\Plugin\Log\Loggable;

class MonduAssistantSettingsHandler implements WizardSettingsHandler
{
    use Loggable;

    private $settingsService;

    public function __construct(SettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    public function handle(array $parameters): bool
    {
        $data = $parameters['data'];

        /** @var SettingsService $settingsService */
        $settingsService = pluginApp(SettingsService::class);

        $settingsService->setData($data);

        $this->registerWebhooks();

        $data['paymentMethods'] = $this->getPaymentMethods();

        $settingsService->updateOrCreateSettings($data, $data['config_name']);
        //TODO quality of life improvements
//        $this->createContainer($webstoreId, $data);
        return true;
    }

    private function getPaymentMethods()
    {
        /** @var ApiClient $apiClient */
        $apiClient = pluginApp(ApiClient::class);
        return array_column($apiClient->getPaymentMethods()['payment_methods'], 'identifier');
    }

    private function registerWebhooks()
    {
        /** @var ApiClient $apiClient */
        $apiClient = pluginApp(ApiClient::class);
        /** @var DomainHelper $domainHelper */
        $domainHelper = pluginApp(DomainHelper::class);

        $apiClient->registerWebhooks([
            'topic' => 'order',
            'address' => $domainHelper->getDomain() . '/mondu/webhooks'
        ]);
    }
}
