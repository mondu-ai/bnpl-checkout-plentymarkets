<?php

namespace Mondu\Procedures;

use Mondu\Services\OrderService;
use Mondu\Traits\MonduCommentTrait;
use Mondu\Traits\MonduMethodTrait;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Models\Order;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;

class OrderCanceled
{
    use MonduMethodTrait, Loggable, MonduCommentTrait;

    public function run(EventProceduresTriggered $eventTriggered)
    {
        if ($eventTriggered->getOrder() instanceof Order) {
            $paymentMethod = $this->getMonduPaymentMethod($eventTriggered->getOrder()->methodOfPaymentId);
            if ($paymentMethod instanceof PaymentMethod) {
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->info("Mondu::Logs.cancelingOrder",[
                        'order_id' => (string) $eventTriggered->getOrder()->id
                    ]);
                /** @var OrderService $orderService */
                $orderService = pluginApp(OrderService::class);
                $response = $orderService->cancelOrder($eventTriggered->getOrder());

                if ($response['error']) {
                    $this->addOrderComments($eventTriggered->getOrder()->id, "couldntCancelOrder");
                } else {
                    $this->addOrderComments($eventTriggered->getOrder()->id, "successfullyCanceledOrder");
                }
            }
        }
    }
}
