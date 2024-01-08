<?php

namespace Mondu\Factories;

use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Plugin\Log\Loggable;

class CreditNoteFactory
{
    use Loggable;
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

        $data = [
            'external_reference_id' => (string) $order->id,
            'gross_amount_cents' => (int) round($orderAmount->invoiceTotal * 100),
            'tax_cents' => (int) round($orderAmount->vatTotal * 100)
        ];

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.refundOrder", [
                'credit_note_factory_data' => $data
            ]);

        return $data;
    }
}
