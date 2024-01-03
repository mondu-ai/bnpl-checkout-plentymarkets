<?php

namespace Mondu\Assistants\Modifiers;

use Mondu\Api\ApiClient;
use Plenty\Modules\Wizard\Contracts\WizardDataModifier as AssistantDataModifier;

class MonduAssistantWebhookSecretModifier implements AssistantDataModifier
{
    public function modify(array $parameters)
    {
        $data = $parameters['data'];

        /** @var ApiClient $apiClient */
        $apiClient =  pluginApp(ApiClient::class);

        $webhookSecretResponse = $apiClient->getWebhookSecret();

        $data['webhookSecret'] = $webhookSecretResponse['webhook_secret'];

        return $data;
    }
}
