<?php
namespace Mondu\Assistants\SettingsHandlers;

use Plenty\Modules\Plugin\Contracts\PluginLayoutContainerRepositoryContract;
use Plenty\Modules\System\Contracts\WebstoreRepositoryContract;
use Plenty\Modules\Wizard\Contracts\WizardSettingsHandler;

class MonduAssistantSettingsHandler implements WizardSettingsHandler
{
    public function handle(array $parameter): bool
    {
        //TODO save settings ( To be done later )

        /**
         * Save the settings within an own function.
         */
//        $this->saveSettings($webstoreId, $data);

        /**
         * Make other configurations after saving these configurations,
         * e.g. creating required container links.
         */
//        $this->createContainer($webstoreId, $data);
        return true;
    }

    private function saveSettings($webstoreId, $data)
    {
        $settings = [
          'plentyId' => $webstoreId
        ];
    }
}
