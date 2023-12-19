<?php

namespace Mondu\Services;

use Mondu\Api\ApiClient;
use Mondu\Contracts\MonduTransactionRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Models\Payment;

class OrderService {
    /**
     * @var PaymentRepositoryContract
     */
    private $paymentRepository;

    /**
     * @var PaymentOrderRelationRepositoryContract
     */
    private $paymentOrderRelationRepositoryContract;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepositoryContract;

    /**
     * @var MonduTransactionRepositoryContract
     */
    private $monduTransactionRepository;

    /**
     * @var ApiClient
     */
    private $apiClient;

    public function __construct(
        PaymentRepositoryContract $paymentRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepositoryContract,
        OrderRepositoryContract $orderRepositoryContract,
        MonduTransactionRepositoryContract $monduTransactionRepository,
        ApiClient $apiClient
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentOrderRelationRepositoryContract = $paymentOrderRelationRepositoryContract;
        $this->orderRepositoryContract = $orderRepositoryContract;
        $this->monduTransactionRepository = $monduTransactionRepository;
        $this->apiClient = $apiClient;
    }

    public function preparePayment($mopId = null)
    {
    }

    public function createPaymentObject(int $mopId, array $monduOrderData = []): Payment
    {
        $transaction = $this->monduTransactionRepository->getMonduTransaction();
        $monduOrderData = $this->apiClient->getOrder($transaction->monduOrderUuid);
        $monduOrder = $monduOrderData['order'];

        $paymentData = [];
        $paymentData['mopId']           = $mopId;
        $paymentData['transactionType'] = 2;
        $paymentData['status']          = $monduOrder['state'] === 'confirmed' ? Payment::STATUS_APPROVED : Payment::STATUS_AWAITING_APPROVAL;
        $paymentData['currency']        = $monduOrder['currency'];
        $paymentData['amount']          = $monduOrder['real_price_cents'] / 100;

        $paymentData['properties'] = [
            [
                'typeId'  => 1,
                'value'   => $monduOrder['uuid']
            ]
        ];

        return $this->paymentRepository->createPayment($paymentData);
    }

    public function assignPlentyPaymentToPlentyOrder(Payment $payment, int $orderId, string $monduOrderUuid)
    {
        $order = $this->orderRepositoryContract->findOrderById($orderId);

        if (!is_null($order) && $order instanceof Order) {
            $this->orderRepositoryContract->updateOrder(
                [
                    'properties' => [
                        ['typeId' => OrderPropertyType::EXTERNAL_ORDER_ID, 'value' => $monduOrderUuid]
                    ]
                ],
                $orderId
            );

            $this->paymentOrderRelationRepositoryContract->createOrderRelation($payment, $order);
        }
    }

    public function cancelOrder(Order $order)
    {
        $monduUuid = $this->getOrderExternalId($order);
        if ($monduUuid) {
            $this->apiClient->cancelOrder($monduUuid);
        }
    }

    protected function getOrderExternalId(Order $order): ?string
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
