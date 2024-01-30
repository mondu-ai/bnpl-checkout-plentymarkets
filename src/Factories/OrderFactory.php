<?php

namespace Mondu\Factories;

use Mondu\Helper\DomainHelper;
use Mondu\Traits\MonduMethodTrait;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\OrderAmount;
use Plenty\Modules\Order\Models\OrderItem;
use Plenty\Modules\Order\Models\OrderItemAmount;
use Plenty\Modules\Order\Models\OrderItemType;
use Plenty\Plugin\Log\Loggable;

class OrderFactory
{
    use MonduMethodTrait, Loggable;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    /**
     * @var DomainHelper
     */
    private $domainHelper;

    /**
     * @var AuthHelper
     */
    private $authHelper;

    public function __construct(
        OrderRepositoryContract $orderRepository,
        DomainHelper $domainHelper,
        AuthHelper $authHelper
    ) {
        $this->orderRepository = $orderRepository;
        $this->domainHelper = $domainHelper;
        $this->authHelper = $authHelper;
    }

    public function buildOrder(int $mopId = null, string $language = 'en', int $orderId = null): array
    {
        try {
            $method = $this->getMonduPaymentMethodName($mopId);

            if (!$orderId) {
                $data = $this->buildCheckoutOrder($method, $language);
            } else {
                // use processUnguarded to find orders for guests
                $data = $this->authHelper->processUnguarded(function () use($orderId, $method, $language) {
                    return $this->buildExistingOrder($orderId, $method, $language);
                });
            }

            $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                ->info("Mondu::Logs.createOrder", [
                    'order_factory_data' => $data
                ]);

            return $data;
        } catch(\Exception $e) {
            $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                ->error("Mondu::Logs.createOrder", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTrace()
                ]);
            return [];
        }
    }

    private function buildExistingOrder($orderId, $method = 'invoice', $language = 'en'): array
    {
        $order = $this->orderRepository->findOrderById($orderId);

        /** @var OrderAmount $orderAmount */
        $orderAmount = $order->amount;

        /** @var Address $billingAddress */
        $billingAddress = $order->billingAddress;

        /** @var Address $deliveryAddress */
        $shippingAddress = $order->deliveryAddress;
        $lineItems = [];
        $shippingPrice = 0;
        $discount = 0;
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem instanceof OrderItem) {
                if ($orderItem->typeId == OrderItemType::TYPE_SHIPPING_COSTS) {
                    $shippingPrice += round($orderItem->amount->priceOriginalNet * 100);
                    continue;
                }

                if($orderItem->typeId == OrderItemType::TYPE_PROMOTIONAL_COUPON) {
                    $discount -= round($orderItem->amount->priceOriginalNet * 100);
                    continue;
                }

                // just in case
                if ($orderItem->amount->priceOriginalNet < 0) {
                    $this->getLogger(__CLASS__ . '::' . __FUNCTION__)->error(
                        'Mondu::Logs.createOrder',
                        [
                            'message' => 'Order item amount is less than 0',
                            'item' => $orderItem
                        ]
                    );
                    continue;
                }

                /** @var OrderItemAmount $amount */
                $amount = $orderItem->amount;
                $lineItems[] = [
                    'external_reference_id' => (string) $orderItem->itemVariationId,
                    'title' => $orderItem->orderItemName,
                    'net_price_per_item_cents' => (int) round($amount->priceNet * 100),
                    'quantity' => $orderItem->quantity,
                    'product_id' => (string) $orderItem->id,
                    'product_sku' => (string) $orderItem->itemVariationId,
                    'item_type' => 'physical',
                ];
            }
        }

        $lines = [[
            'line_items' => $lineItems,
            'tax_cents' => (int) round($orderAmount->grossTotal * 100 - $orderAmount->netTotal * 100),
            'shipping_price_cents' => (int) $shippingPrice,
            'buyer_fee_cents' => 0
        ]];

        $orderData = [
            'currency' => $orderAmount->currency,
            'state_flow' => 'authorization_flow',
            'external_reference_id' => (string) 'PLENTY_' . $orderId,
            'billing_address' =>  [
                'country_code' => $billingAddress->country->isoCode2,
                'state' => $billingAddress->state->name,
                'city' => $billingAddress->town,
                'zip_code' => (string) $billingAddress->postalCode,
                'address_line1' => $billingAddress->street . ' ' . $billingAddress->houseNumber,
            ],
            'shipping_address' =>  [
                'country_code' => $shippingAddress->country->isoCode2,
                'state' =>  $shippingAddress->state->name,
                'city' => $shippingAddress->town,
                'zip_code' => $shippingAddress->postalCode,
                'address_line1' => $shippingAddress->street . ' ' . $shippingAddress->houseNumber,
            ],
            'gross_amount_cents' => (int) round($orderAmount->invoiceTotal * 100),
            'discount_cents' => (int) $discount,
            'buyer' => [
                'email' => $billingAddress->email,
                'first_name' => $billingAddress->firstName,
                'last_name' => $billingAddress->lastName,
                'company_name' => $billingAddress->companyName,
                'phone' => $billingAddress->phone,
            ],
            'lines' => $lines,
            'payment_method' => $method,
            'language' => $language,
            'success_url' => $this->getDomain() . '/mondu/confirm_existing/',
            'cancel_url' => $this->getDomain() . '/mondu/cancel/?order_id=' . $orderId,
            'declined_url' => $this->getDomain() . '/mondu/cancel/?order_id=' . $orderId,
        ];

        return $this->removeEmptyData($orderData);
    }

    private function buildCheckoutOrder($method = 'invoice', $language = 'en'): array
    {
        /** @var BasketRepositoryContract $basketRepository */
        $basketRepository = pluginApp(BasketRepositoryContract::class);

        $basket = $basketRepository->load();

        $addressRepository = pluginApp(AddressRepositoryContract::class);

        $billingAddressId  = $basket->customerInvoiceAddressId;
        $shippingAddressId = $basket->customerShippingAddressId;

        if (is_null($shippingAddressId) || $shippingAddressId == -99) {
            $shippingAddressId = $billingAddressId;
        }

        $billingAddress = $addressRepository->findAddressById($billingAddressId);
        $shippingAddress = $addressRepository->findAddressById($shippingAddressId);

        /** @var ItemRepositoryContract $itemContract */
        $itemContract = pluginApp(ItemRepositoryContract::class);
        $sessionStorage = pluginApp(FrontendSessionStorageFactoryContract::class);

        $lineItems = [];
        $lang = $sessionStorage->getLocaleSettings()->language;

        foreach ($basket->basketItems as $basketItem) {
            $basketItemPrice = $basketItem->price + $basketItem->attributeTotalMarkup;
            $basketItemPrice = (int) round(100 * ($basketItemPrice * 100) / (100.0 + $basketItem->vat));

            try {
                $item = $itemContract->show($basketItem->itemId, ['*'], $lang);
                $itemTitle = $item->texts->first()->name1;
            } catch (\Exception $e) {
                $this->getLogger(__CLASS__ . '::' . __FUNCTION__)->error(
                    'Mondu::Logs.createOrder',
                    [
                        'message' => $e->getMessage(),
                        'trace' => $e->getTrace()
                    ]
                );
                $itemTitle = '-';
            }

            $lineItems[] = [
                'external_reference_id' => (string) $basketItem->variationId,
                'title' => $itemTitle,
                'net_price_per_item_cents' => $basketItemPrice,
                'quantity' => $basketItem->quantity,
                'product_id' => (string) $basketItem->itemId,
                'product_sku' => (string) $basketItem->variationId,
                'item_type' => 'physical'
            ];
        }

        $lines = [[
            'line_items' => $lineItems,
            'tax_cents' => (int) round($basket->basketAmount * 100 - $basket->basketAmountNet * 100),
            'shipping_price_cents' => (int) round($basket->shippingAmountNet * 100),
            'buyer_fee_cents' => 0
        ]];

        $orderData = [
            'currency' => $basket->currency,
            'state_flow' => 'authorization_flow',
            'external_reference_id' => uniqid('PLENTY_'),
            'billing_address' => [
                'country_code' => $billingAddress->country->isoCode2,
                'state' => $billingAddress->state->name,
                'city' => $billingAddress->town,
                'zip_code' => (string) $billingAddress->postalCode,
                'address_line1' => $billingAddress->street . ' ' . $billingAddress->houseNumber,
            ],
            'shipping_address' => [
                'country_code' => $shippingAddress->country->isoCode2,
                'state' =>  $shippingAddress->state->name,
                'city' => $shippingAddress->town,
                'zip_code' => (string) $shippingAddress->postalCode,
                'address_line1' => $shippingAddress->street . ' ' . $shippingAddress->houseNumber,
            ],
            'gross_amount_cents' => (int) round($basket->basketAmount * 100),
            'discount_cents' => (int) round($basket->couponDiscount * 100),
            'buyer' => [
                'email' => $billingAddress->email,
                'first_name' => $billingAddress->firstName,
                'last_name' => $billingAddress->lastName,
                'company_name' => $billingAddress->companyName,
                'phone' => $billingAddress->phone,
                'is_registered' => false
            ],
            'lines' => $lines,
            'payment_method' => $method,
            'language' => $language,
            'success_url' => $this->getDomain() . '/' . $lang . '/mondu/confirm/',
            'cancel_url' => $this->getDomain() . '/' . $lang . '/mondu/cancel/',
            'declined_url' => $this->getDomain() . '/' . $lang .  '/mondu/cancel/',
        ];

        return $this->removeEmptyData($orderData);
    }

    protected function getDomain(): string
    {
        return $this->domainHelper->getDomain();
    }

    protected function removeEmptyData(array $array): array {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->removeEmptyData($value);
            }

            if ($value === '' || $value === null) {
                unset($array[$key]);
            }
        }
        return $array;
    }
}
