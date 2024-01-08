<?php
namespace Mondu\Assistants;

use Mondu\Assistants\Handlers\MonduAssistantActionsHandler;
use Mondu\Assistants\Modifiers\MonduAssistantWebhookSecretModifier;
use Mondu\Assistants\SettingsHandlers\MonduAssistantSettingsHandler;
use Mondu\Assistants\Validators\MonduApiKeyValidator;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\System\Models\Webstore;
use Plenty\Modules\Wizard\Services\WizardProvider;
use Plenty\Plugin\Application;

class MonduAssistant extends WizardProvider
{
    /**
     * @var WebstoreRepositoryContract
     */
    private $webstoreRepository;

    /**
     * @var array
     */
    private $webstoreValues;

    public function __construct(
        WebstoreRepositoryContract $webstoreRepository
    ) {
        $this->webstoreRepository = $webstoreRepository;
    }

    /**
     *  In this method we define the basic settings and the structure of the assistant in an array.
     *  Here, we have to define aspects like the topic, settings handler, steps and form elements.
     */
    protected function structure(): array
    {
        return [
            "title" => 'MonduAssistant.assistantTitle',
            "shortDescription" => 'MonduAssistant.assistantShortDescription',
            "iconPath" => $this->getIcon(),
            "settingsHandlerClass" => MonduAssistantSettingsHandler::class,
            'actionHandlerClass' => MonduAssistantActionsHandler::class,
            "translationNamespace" => "Mondu",
            "key" => "payment-mondu-assistant",
            /** The topic needs to be payment. */
            "topics" => ["payment"],
            "priority" => 100,
            "options" => [
                "config_name" => [
                    "type" => 'select',
                    'defaultValue' => [],
                    /** We need a list of all webstores to configure each individually. */
                    "options" => [
                        "name" => 'MonduAssistant.storeName',
                        'required' => true,
                        'listBoxValues' => $this->getWebstoreListForm(),
                    ],
                ],
            ],
            /** Define steps for the assistant. */
            "steps" => [
                "stepOne" => [
                    "title" => "MonduAssistant.stepOneTitle",
                    "sections" => [
                        [
                            "title" => 'MonduAssistant.shippingCountriesTitle',
                            "description" => 'MonduAssistant.shippingCountriesDescription',
                            "form" => [
                                "shippingCountries" => [
                                    'type' => 'checkboxGroup',
                                    'defaultValue' => [],
                                    'options' => [
                                        'name' => 'MonduAssistant.shippingCountries',
                                        'checkboxValues' => [
                                            [
                                                'caption' => 'MonduAssistant.germany',
                                                'value' => 'DE',
                                            ],
                                            [
                                                'caption' => 'MonduAssistant.austria',
                                                'value' => 'AT',
                                            ],
                                            [
                                                'caption' => 'MonduAssistant.france',
                                                'value' => 'FR',
                                            ],
                                            [
                                                'caption' => 'MonduAssistant.uk',
                                                'value' => 'UK',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                "stepTwo" => [
                    "title" => "MonduAssistant.stepTwoTitle",
                    "validationClass" => MonduApiKeyValidator::class,
                    "modifierClass" => MonduAssistantWebhookSecretModifier::class,
                    "sections" => [
                        [
                            "title" => 'MonduAssistant.apiKeyTitle',
                            "description" => 'MonduAssistant.apiKeyDesc',
                            "form" => [
                                'apiKey' => [
                                    'type' => 'text',
                                    'options' => [
                                        'name' => 'MonduAssistant.apiKey',
                                        'required' => true
                                    ]
                                ],
                                'useSandbox' => [
                                    'type' => 'radioGroup',
                                    'defaultValue' => 1,
                                    'options' => [
                                        'required' => true,
                                        'inline' => false,
                                        'name' => 'MonduAssistant.useSandbox',
                                        'radioValues' => [
                                            [
                                                'caption' => 'MonduAssistant.useMonduSandbox',
                                                'value' => 1
                                            ],
                                            [
                                                'caption' => 'MonduAssistant.useMonduLive',
                                                'value' => 0
                                            ]
                                        ]
                                    ]
                                ],
                                'webhookSecret' => [
                                    'type' => 'text',
                                    'options' => [
                                        'isReadonly' => true,
                                        'name' => 'MonduAssistant.webhookSecret',
                                        'required' => false
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getIcon(): string
    {
        return 'https://checkout.mondu.ai/logo.svg';
    }

    private function getWebstoreListForm(): array
    {
        if ($this->webstoreValues === null) {
            $webstores = $this->webstoreRepository->loadAll();
            /** @var Webstore $webstore */
            foreach ($webstores as $webstore) {
                $this->webstoreValues[] = [
                    "caption" => $webstore->name,
                    "value" => $webstore->storeIdentifier,
                ];
            }

            usort($this->webstoreValues, function ($a, $b) {
                return ($a['value'] <=> $b['value']);
            });
        }

        return $this->webstoreValues;
    }
}
