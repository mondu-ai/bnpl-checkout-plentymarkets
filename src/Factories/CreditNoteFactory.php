<?php

namespace Mondu\Factories;

use Plenty\Modules\Order\Contracts\OrderRepositoryContract;

class CreditNoteFactory
{
    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    public function __construct(
        OrderRepositoryContract $orderRepository
    ) {
        $this->orderRepository = $orderRepository;
    }
    public function buildCreditNote(int $orderId): array
    {
        $order = $this->orderRepository->findOrderById($orderId);
        $orderAmount = $order->amount;

        return [
            'external_reference_id' => (string) $order->id,
            'gross_amount_cents' => (int) round($orderAmount->invoiceTotal * 100),
            'tax_cents' => (int) round($orderAmount->vatTotal * 100)
        ];
    }
}