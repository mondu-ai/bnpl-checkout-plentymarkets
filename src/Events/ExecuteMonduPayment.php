<?php
namespace Mondu\Events;

use Mondu\Api\ApiClient;
use Mondu\Contracts\MonduTransactionRepositoryContract;
use Mondu\Services\OrderService;
use Mondu\Traits\MonduMethodTrait;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Translation\Translator;

class ExecuteMonduPayment
{
    use MonduMethodTrait, Loggable;

    /**
     * @var OrderService
     */
    private $orderService;

    /**
     * @var MonduTransactionRepositoryContract
     */
    private $monduTransactionRepository;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var Translator
     */
    private $translator;

    public function __construct(
        OrderService $orderService,
        MonduTransactionRepositoryContract $monduTransactionRepository,
        ApiClient $apiClient,
        Translator $translator
    ) {
        $this->orderService = $orderService;
        $this->monduTransactionRepository = $monduTransactionRepository;
        $this->apiClient = $apiClient;
        $this->translator = $translator;
    }

    public function handle(ExecutePayment $event) {
        $paymentMethod = $this->getMonduPaymentMethod($event->getMop());

        if ($paymentMethod instanceof PaymentMethod) {
            try {
                $monduTransaction = $this->monduTransactionRepository->getMonduTransaction();
                $this->monduTransactionRepository->setOrderId($event->getOrderId());
                $data = $this->apiClient->confirmOrder($monduTransaction->monduOrderUuid, ['external_reference_id' => (string) $event->getOrderId()]);
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->info("Mondu::Logs.confirmOrder", [
                        'confirm_order_data' => $data,
                        'flow' => 'Checkout flow'
                    ]);

                $this->orderService->assignPlentyPaymentToPlentyOrder($this->orderService->createPaymentObject($paymentMethod->id), $event->getOrderId(), $data['order']['uuid']);
            } catch(\Exception $e) {
                $event->setType('error');
                $event->setValue($this->translator->trans('Mondu::Errors.errorPlacingOrder'));

                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->error("Mondu::Logs.confirmOrder", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTrace()
                    ]);
            }
        }
    }
}
