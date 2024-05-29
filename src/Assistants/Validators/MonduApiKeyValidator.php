<?php

namespace Mondu\Assistants\Validators;

use Illuminate\Support\MessageBag;
use Mondu\Api\ApiClient;
use Mondu\Services\SettingsService;
use Plenty\Exceptions\ValidationException;
use Plenty\Validation\Validator;

class MonduApiKeyValidator extends Validator
{
    public static function validateOrFail(array $data)
    {
        /** @var ApiClient $apiClient */
        $apiClient =  pluginApp(ApiClient::class);

        $data = $apiClient->setApiKey($data['apiKey'])
            ->setApiUrl($data['useSandbox'] ? SettingsService::DEMO_API_URL : SettingsService::LIVE_API_URL)
            ->getWebhookSecret();

        if (isset($data['error'])) {
            $messageBag = pluginApp(
                MessageBag::class,
                [
                    [
                        'key' => 'Invalid API key'
                    ]
                ]
            );
            /** @var ValidationException $exception */
            $exception = pluginApp(ValidationException::class, ['Invalid Api Key']);
            $exception->setMessageBag($messageBag);
            throw $exception;
        }

        parent::validateOrFail($data);
    }

    protected function defineAttributes()
    {
        // Silence is golden
    }
}
