<?php

namespace Mondu\Assistants\Modifiers;

use Mondu\Api\ApiClient;
use Mondu\Services\SettingsService;
use Plenty\Modules\Wizard\Contracts\WizardDataModifier as AssistantDataModifier;

class MonduAssistantWebhookSecretModifier implements AssistantDataModifier
{
    public function modify(array $parameters)
    {
        $data = $parameters['data'];

        /** @var ApiClient $apiClient */
        $apiClient =  pluginApp(ApiClient::class);

        if (isset($data['apiKey'])) {
            $webhookSecretResponse = $apiClient->setApiKey($data['apiKey'])
                ->setApiUrl($data['useSandbox'] ? SettingsService::DEMO_API_URL : SettingsService::LIVE_API_URL)
                ->getWebhookSecret();

            $data['webhookSecret'] = $webhookSecretResponse['webhook_secret'];
        }

        return $data;
    }
}
