<?php
namespace Mondu\Migrations;

use Mondu\Models\MonduSettings;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreateSettingsTable_02
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable(MonduSettings::class);
    }
}
