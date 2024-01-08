<?php

namespace Mondu\Services;

use Mondu\Api\ApiClient;
use Mondu\Contracts\MonduTransactionRepositoryContract;
use Mondu\Factories\CreditNoteFactory;
use Mondu\Factories\InvoiceFactory;
use Mondu\Helper\OrderHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Models\OrderType;
use Plenty\Modules\Order\Property\Models\OrderProperty;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Plugin\Log\Loggable;

class OrderService {

    use Loggable;
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

    /**
     * @var InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var CreditNoteFactory
     */
    private $creditNoteFactory;

    public function __construct(
        PaymentRepositoryContract $paymentRepository,
        PaymentOrderRelationRepositoryContract $paymentOrderRelationRepositoryContract,
        OrderRepositoryContract $orderRepositoryContract,
        MonduTransactionRepositoryContract $monduTransactionRepository,
        InvoiceFactory $invoiceFactory,
        ApiClient $apiClient,
        OrderHelper $orderHelper,
        CreditNoteFactory $creditNoteFactory
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->paymentOrderRelationRepositoryContract = $paymentOrderRelationRepositoryContract;
        $this->orderRepositoryContract = $orderRepositoryContract;
        $this->monduTransactionRepository = $monduTransactionRepository;
        $this->apiClient = $apiClient;
        $this->invoiceFactory = $invoiceFactory;
        $this->orderHelper = $orderHelper;
        $this->creditNoteFactory = $creditNoteFactory;
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
    public function createRefundObject($payments, $creditNoteData): Payment
    {
        foreach($payments as $payment) {
            $mop = $payment->mopId;
            $parentPaymentId = $payment->id;
        }

        $refundTid = $creditNoteData['uuid'];
        /** @var Payment $payment */
        $payment = pluginApp(\Plenty\Modules\Payment\Models\Payment::class);
        $payment->updateOrderPaymentStatus = true;
        $payment->mopId = (int) $mop;
        $payment->transactionType = Payment::TRANSACTION_TYPE_BOOKED_POSTING;
        $payment->status = Payment::STATUS_APPROVED;
        $payment->currency = $creditNoteData['currency'];
        $payment->amount = $creditNoteData['gross_amount_cents'] / 100;
        $payment->receivedAt = date('Y-m-d H:i:s');
        $payment->type = 'debit';
        $payment->parentId = $parentPaymentId;
        $payment->unaccountable = 0;
        $paymentProperty     = [];

        $comments = $creditNoteData['notes'] ?? '';
        $paymentProperty[]   = $this->getPaymentProperty(PaymentProperty::TYPE_BOOKING_TEXT, $comments);
        $paymentProperty[]   = $this->getPaymentProperty(PaymentProperty::TYPE_TRANSACTION_ID, $refundTid);
        $paymentProperty[]   = $this->getPaymentProperty(PaymentProperty::TYPE_ORIGIN, Payment::ORIGIN_PLUGIN);
        $paymentProperty[]   = $this->getPaymentProperty(PaymentProperty::TYPE_EXTERNAL_TRANSACTION_STATUS, $creditNoteData['state']);
        $payment->properties = $paymentProperty;
        return $this->paymentRepository->createPayment($payment);
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
        $monduUuid = $this->orderHelper->getOrderExternalId($order);

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.cancelingOrder",[
                'mondu_uuid' => (string) $monduUuid
            ]);

        if ($monduUuid) {
            $this->apiClient->cancelOrder($monduUuid);
        }
    }

    public function createOrderInvoice(Order $order)
    {
        $monduUuid = $this->orderHelper->getOrderExternalId($order);
        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.creatingInvoice",[
                'mondu_uuid' => (string) $monduUuid
            ]);

        if ($monduUuid) {
            try {
                $data = $this->apiClient->createInvoice($monduUuid, $this->invoiceFactory->buildInvoice($order->id));
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->info("Mondu::Logs.creatingInvoice",[
                        'invoice_data' => $data
                    ]);
            } catch(\Exception $e) {
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->error("Mondu::Logs.creatingInvoice", [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTrace()
                    ]);
            }
        }
    }

    public function createRefund(Order $order)
    {
        $monduUuid = $this->orderHelper->getOrderExternalId($order);
        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.refundOrder",[
                'mondu_uuid' => (string) $monduUuid
            ]);

        if($monduUuid && $order->typeId == OrderType::TYPE_CREDIT_NOTE) {
            try {
                $invoicesResponse = $this->apiClient->getInvoices($monduUuid);

                $invoices = $invoicesResponse['invoices'];

                $invoiceUuid = $invoices[0]['uuid'];

                $creditNoteResponse = $this->apiClient->createCreditNote($invoiceUuid, $this->creditNoteFactory->buildCreditNote($order->id));

                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->info("Mondu::Logs.refundOrder",[
                        'credit_note_data' => $creditNoteResponse
                    ]);

                $parentOrderId = $order->id;

                foreach($order->orderReferences as $orderReference) {
                    $parentOrderId = $orderReference->originOrderId;
                }

                $paymentDetails = $this->paymentRepository->getPaymentsByOrderId($parentOrderId);

                $refund = $this->createRefundObject($paymentDetails, $creditNoteResponse['credit_note']);
                $this->assignPlentyPaymentToPlentyOrder($refund, $order->id, $monduUuid);
            } catch(\Exception $e) {
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->error("Mondu::Logs.refundOrder", [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTrace()
                    ]);
            }
        }
    }

    public function getPaymentProperty($typeId, $value)
    {
        /** @var PaymentProperty $paymentProperty */
        $paymentProperty = pluginApp(\Plenty\Modules\Payment\Models\PaymentProperty::class);

        $paymentProperty->typeId = $typeId;
        $paymentProperty->value  = (string) $value;

        return $paymentProperty;
    }
}
