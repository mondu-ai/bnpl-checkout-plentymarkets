<?php

namespace Mondu\Procedures;

use Mondu\Services\OrderService;
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
            $paymentMethod = $this->getMonduPaymentMethod($eventTriggered->getOrder()->methodOfPaymentId);

            if ($paymentMethod instanceof PaymentMethod) {
                /** @var OrderService $orderService */
                $orderService = pluginApp(OrderService::class);
                $orderService->createOrderInvoice($eventTriggered->getOrder());
            }
        }
    }
}
