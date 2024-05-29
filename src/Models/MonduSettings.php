<?php

namespace Mondu\Models;


use Plenty\Modules\Plugin\DataBase\Contracts\Model;

class MonduSettings extends Model
{
    /**
     * @var int
     */
    public $id = 0;

    /**
     * @var int
     */
    public $webstore = 0;

    /**
     * @var array
     */
    public $value = array();

    /**
     * @var int
     */
    public $timestamp = 0;

    public function getTableName(): string
    {
        return 'Mondu::MonduSettings';
    }
}