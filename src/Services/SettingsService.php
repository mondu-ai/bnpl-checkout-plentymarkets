<?php

namespace Mondu\Services;

use Mondu\Models\MonduSettings;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;
use Plenty\Modules\Plugin\PluginSet\Contracts\PluginSetRepositoryContract;
use Plenty\Plugin\Application;

class SettingsService {
    public const PLUGIN_VERSION = '1.0.0';
    public const PLUGIN_NAME = 'plentymarkets';
    public const DEMO_API_URL = 'https://api.demo.mondu.ai/api/v1';
    public const LIVE_API_URL = 'https://api.mondu.ai/api/v1';

    protected $data;

    /**
     * @var DataBase
     */
    protected $dataBase;

    public function __construct(
        DataBase $dataBase
    ) {
        $this->dataBase = $dataBase;
    }

    public function getSetting(string $name)
    {
        if (!$this->data) {
            $this->loadSettings();
        }

        return $this->data[$name] ?? null;
    }

    public function getApiUrl(): string
    {
        return $this->getSetting('useSandbox') ? self::DEMO_API_URL : self::LIVE_API_URL;
    }

    public function getPluginVersion(): string
    {
        return self::PLUGIN_VERSION;
    }

    public function getPluginName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getSettings() {
        return $this->dataBase->query(MonduSettings::class)
            ->get();
    }

    public function updateOrCreateSettings(array $data, int $webstoreId = null): bool
    {
        $webstoreId = $this->getWebstoreId($webstoreId);

        /** @var MonduSettings $monduSettings */
        $monduSettings = pluginApp(MonduSettings::class);

        $setting = $this->dataBase->query(MonduSettings::class)
            ->where('webstore', '=', $webstoreId)
            ->limit(1)
            ->get();

        if(is_array($setting) && $setting[0] instanceof MonduSettings) {
            $setting = $setting[0];
            $setting->value = $data;
            $this->dataBase->save($setting);
            return true;
        }

        $monduSettings->value = $data;
        $monduSettings->webstore = $webstoreId;

        $this->dataBase->save($monduSettings);
        return true;
    }

    public function setData(array $data)
    {
        $this->data = $data;
    }

    private function getWebstoreId(int $webstoreId = null): int
    {
        if (is_null($webstoreId)) {
            /** @var Application $application */
            $application = pluginApp(Application::class);
            $webstoreId = $application->getPlentyId();
        }

        return $webstoreId;
    }

    private function loadSettings(int $webstoreId = null)
    {
        $webstoreId = $this->getWebstoreId($webstoreId);

        $settings = $this->dataBase->query(MonduSettings::class)
            ->where('webstore', '=', $webstoreId)
            ->limit(1)
            ->get();

        $this->data = $settings[0]->value;
    }
}
