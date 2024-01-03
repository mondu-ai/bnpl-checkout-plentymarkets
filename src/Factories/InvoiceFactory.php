<?php

namespace Mondu\Factories;

use Mondu\Helper\DomainHelper;
use Mondu\Helper\OrderHelper;
use Plenty\Modules\Cloud\Storage\Models\StorageObject;
use Plenty\Modules\Document\Models\Document;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Documents\Contracts\OrderDocumentStorageContract;
use Plenty\Modules\Order\Models\OrderAmount;

class InvoiceFactory
{
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var OrderDocumentStorageContract
     */
    private $orderDocumentStorage;

    /**
     * @var DomainHelper
     */
    private $domainHelper;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    public function __construct(
        OrderRepositoryContract $orderRepository,
        OrderDocumentStorageContract $orderDocumentStorage,
        DomainHelper $domainHelper,
        OrderHelper $orderHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderDocumentStorage = $orderDocumentStorage;
        $this->domainHelper = $domainHelper;
        $this->orderHelper = $orderHelper;
    }

    public function buildInvoice(int $orderId = null): array
    {
        $order = $this->orderRepository->findOrderById($orderId);

        /** @var OrderAmount $orderAmount */
        $orderAmount = $order->amount;

        /** @var Document[] $documents */
        $documents = $order->documents;

        $documentData = [];

        $invoiceDoc = null;

        foreach ($documents as $document) {
            if ($document->type === Document::INVOICE) {
                $invoiceDoc = $document->toArray();
            }
        }

        return [
            'external_reference_id' => (string) $invoiceDoc['numberWithPrefix'] ?? $orderId,
            'gross_amount_cents' => (int) round($orderAmount->invoiceTotal * 100),
            'tax_cents' => (int) round($orderAmount->vatTotal * 100),
            'shipping_price_cents' => (int) round($orderAmount->shippingCostsNet * 100),
            'documents' => $documentData,
            'invoice_url' => $this->domainHelper->getDomain() . '/mondu/invoice/?order_uuid=' . $this->orderHelper->getOrderExternalId($order)
        ];
    }
}
