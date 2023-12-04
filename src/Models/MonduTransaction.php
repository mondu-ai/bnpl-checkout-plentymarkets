<?php

namespace Mondu\Models;

use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class MonduTransaction extends Model
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $orderId = 0;

    /**
     * @var string
     */
    public $monduOrderUuid = '';

    /**
     * @var int
     */
    public $timestamp = 0;

    /**
     * @var string
     */
    public $monduStatus = '';

    public function getTableName(): string
    {
        return 'Mondu::MonduTransaction';
    }
}
