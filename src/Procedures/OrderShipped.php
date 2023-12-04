<?php

namespace Mondu\Procedures;

use Mondu\Traits\MonduMethodTrait;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Log\Loggable;

class OrderShipped
{
    use MonduMethodTrait, Loggable;

    public function run(EventProceduresTriggered $eventTriggered)
    {
        if ($eventTriggered->getOrder() instanceof Order) {
            //TODO implement
        }
    }
}
