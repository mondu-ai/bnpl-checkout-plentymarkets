<?php
namespace Mondu\Assistants;

use Mondu\Assistants\SettingsHandlers\MonduAssistantSettingsHandler;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\Wizard\Services\WizardProvider;
use Plenty\Plugin\Application;

//TODO connected with plugin configuration ( To be done later )
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
            /** Use translate keys for multilingualism. */
            "title" => 'MonduAssistant.assistantTitle',
            "shortDescription" => 'MonduAssistant.assistantShortDescription',
            /** Add our settings handler class. */
            "settingsHandlerClass" => MonduAssistantSettingsHandler::class,
            "translationNamespace" => "Mondu",
            "key" => "payment-mondu-assistant",
            /** The topic needs to be payment. */
            "topics" => ["payment"],
            "priority" => 990,
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
                /** We need a caption and a value because it is a drop-down menu. */
                $this->webstoreValues[] = [
                    "caption" => $webstore->name,
                    "value" => $webstore->storeIdentifier,
                ];
            }

            /** Sort the array for better usability. */
            usort($this->webstoreValues, function ($a, $b) {
                return ($a['value'] <=> $b['value']);
            });
        }

        return $this->webstoreValues;
    }
}
