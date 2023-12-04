<?php
namespace Mondu\Events;

use Mondu\Api\ApiClient;
use Mondu\Factories\OrderFactory;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Mondu\Traits\MonduMethodTrait;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;

class PrepareMonduPayment
{
    use MonduMethodTrait, Loggable;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var FrontendSessionStorageFactoryContract
     */
    private $frontendSessionStorageFactory;

    public function __construct(
        ApiClient $apiClient,
        OrderFactory $orderFactory,
        FrontendSessionStorageFactoryContract $frontendSessionStorageFactory

    ) {
        $this->apiClient = $apiClient;
        $this->orderFactory = $orderFactory;
        $this->frontendSessionStorageFactory = $frontendSessionStorageFactory;
    }

    public function handle(GetPaymentMethodContent $event) {
        $paymentMethod = $this->getMonduPaymentMethod($event->getMop());

        if ($paymentMethod instanceof PaymentMethod) {
            try {
                $lang = $this->frontendSessionStorageFactory->getLocaleSettings()->language;

                $data = $this->apiClient->createOrder($this->orderFactory->buildOrder($event->getMop(), $lang));
                $event->setValue($data['order']['hosted_checkout_url']);
                $event->setType('redirectUrl');
            } catch(\Exception $exception) {
                $event->setType(GetPaymentMethodContent::RETURN_TYPE_ERROR);
                $event->setValue($exception->getMessage());
                $this->getLogger('creatingOrder')->logException($exception);
            }
        }
    }
}
