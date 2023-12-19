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

        $settingsService->updateOrCreateSettings($data, $data['config_name']);

        $this->settingsService->setData($data);

        $this->getWebhookSecret();

        //TODO implement webhook endpints and functionality
        $this->registerWebhooks();

        //TODO
//        $this->createContainer($webstoreId, $data);
        return true;
    }

    private function getWebhookSecret()
    {
        /** @var ApiClient $apiClient */
        $apiClient = pluginApp(ApiClient::class);

        //TODO save
        $apiClient->getWebhookSecret();
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
