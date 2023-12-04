<?php
namespace Mondu\Migrations;

use Mondu\Models\MonduTransaction;
use Plenty\Modules\Plugin\DataBase\Contracts\Migrate;

class CreateMonduTransactionTable_02
{
    public function run(Migrate $migrate)
    {
        $migrate->createTable(MonduTransaction::class);
    }
}
