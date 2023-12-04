<?php

namespace Mondu\Services;

class SettingsService {
    public const PLUGIN_VERSION = '0.0.1';
    public const DEMO_API_URL = 'https://api.stage.mondu.ai/api/v1';
    public const LIVE_API_URL = 'https://api.mondu.ai/api/v1';

    protected $data = [
        'apiToken' => '4ESFSXUCGHZKNC9EQD2IDUYQERMICPYC',
        'demo' => true
    ];

    //TODO
    public function getSetting(string $name, $lang = 'de')
    {
        return $this->data[$name] ?? null;
    }

    public function getApiUrl(): string
    {
        return $this->getSetting('demo') ? self::DEMO_API_URL : self::LIVE_API_URL;
    }

    public function getPluginVersion(): string
    {
        return self::PLUGIN_VERSION;
    }
}
