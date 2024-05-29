<?php

namespace Mondu\Helper;

use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;

class OrderHelper
{
    public function getOrderExternalId(Order $order): ?string
    {
        foreach ($order->properties as $orderProperty) {
            if ($orderProperty instanceof OrderProperty) {
                if ($orderProperty->typeId == OrderPropertyType::EXTERNAL_ORDER_ID && !empty($orderProperty->value)) {
                    return $orderProperty->value;
                }
            }
        }

        return null;
    }
}
