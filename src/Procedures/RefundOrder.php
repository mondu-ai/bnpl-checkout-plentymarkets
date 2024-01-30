<?php

namespace Mondu\Procedures;

use Mondu\Services\OrderService;
use Mondu\Traits\MonduCommentTrait;
use Mondu\Traits\MonduMethodTrait;
use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Log\Loggable;

class RefundOrder
{
    use MonduMethodTrait, Loggable, MonduCommentTrait;

    public function run(EventProceduresTriggered $eventTriggered)
    {
        if ($eventTriggered->getOrder() instanceof Order) {
            $paymentMethod = $this->getMonduPaymentMethod($eventTriggered->getOrder()->methodOfPaymentId);
            if ($paymentMethod instanceof PaymentMethod) {
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->info("Mondu::Logs.refundOrder",[
                        'order_id' => (string) $eventTriggered->getOrder()->id
                    ]);
                /** @var OrderService $orderService */
                $orderService = pluginApp(OrderService::class);
                $response =  $orderService->createRefund($eventTriggered->getOrder());

                if ($response['error']) {
                    $this->addOrderComments($eventTriggered->getOrder()->id, "couldntCreateCreditNote");
                } else {
                    $this->addOrderComments($eventTriggered->getOrder()->id, "successfullyCreatedCreditNote");
                }
            }
        }
    }
}
