<?php

namespace Mondu\Factories;

use Mondu\Traits\MonduMethodTrait;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Helper\Services\WebstoreHelper;
use Plenty\Modules\Item\Item\Contracts\ItemRepositoryContract;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Order\Models\OrderAmount;
use Plenty\Modules\Order\Models\OrderItem;
use Plenty\Modules\Order\Models\OrderItemAmount;
use Plenty\Modules\Order\Models\OrderItemType;

class OrderFactory
{
    use MonduMethodTrait;

    /**
     * @var OrderRepositoryContract
     */
    private $orderRepository;

    public function __construct(OrderRepositoryContract $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function buildOrder(int $mopId = null, string $language = 'en', int $orderId = null): array
    {
        try {
            $method = $this->getMonduPaymentMethodName($mopId);

            if (!$orderId) {
                return $this->buildCheckoutOrder($method, $language);
            }

            return $this->buildExistingOrder($orderId, $method, $language);
        } catch(\Exception $e) {
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
        foreach ($order->orderItems as $orderItem) {
            if ($orderItem instanceof OrderItem) {
                //TODO handle order items that are not products...
                if ($orderItem->typeId == OrderItemType::TYPE_SHIPPING_COSTS) {
                    $shippingPrice = $orderItem->amount->priceOriginalGross;
                    continue;
                }
                /** @var OrderItemAmount $amount */
                $amount = $orderItem->amount;
                $lineItems[] = [
                    'external_reference_id' => (string) $orderItem->itemVariationId,
                    'title' => $orderItem->orderItemName,
                    'net_price_per_item_cents' => (int) round($amount->priceOriginalNet * 100),
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
            'shipping_price_cents' => (int) round($shippingPrice * 100),
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
            'buyer' => [
                'email' => $billingAddress->email,
                'first_name' => $billingAddress->firstName,
                'last_name' => $billingAddress->lastName,
                'company_name' => $billingAddress->companyName,
                'phone' => $billingAddress->phone,
                'is_registered' => false,
            ],
            'lines' => $lines,
            'payment_method' => $method,
            'language' => $language,
            'success_url' => $this->getDomain() . '/mondu/confirm_existing',
            'cancel_url' => $this->getDomain() . '/mondu/confirm_existing',
            'declined_url' => $this->getDomain() . '/mondu/confirm_existing',
        ];

        return $this->removeEmptyData($orderData);
    }

    private function buildCheckoutOrder($method = 'invoice', $language = 'en'): array
    {
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

        $itemContract = pluginApp(ItemRepositoryContract::class);
        $sessionStorage = pluginApp(FrontendSessionStorageFactoryContract::class);

        $lineItems = [];
        foreach ($basket->basketItems as $basketItem) {
            $basketItemPrice = $basketItem->price + $basketItem->attributeTotalMarkup;
            $basketItemPrice = (int) round(100 * ($basketItemPrice * 100) / (100.0 + $basketItem->vat));
            $item = $itemContract->show($basketItem->itemId, ['*'], $sessionStorage->getLocaleSettings()->language);

            $lineItems[] = [
                'external_reference_id' => (string) $basketItem->variationId,
                'title' => $item->texts->first()->name1,
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
            'success_url' => $this->getDomain() . '/mondu/confirm',
            'cancel_url' => $this->getDomain() . '/mondu/confirm',
            'declined_url' => $this->getDomain() . '/mondu/confirm',
        ];

        return $this->removeEmptyData($orderData);
    }

    protected function getDomain(): string
    {
        /** @var WebstoreHelper $webstoreHelper */
        $webstoreHelper = pluginApp(WebstoreHelper::class);

        $webstoreConfig = $webstoreHelper->getCurrentWebstoreConfiguration();

        $domain = $webstoreConfig->domainSsl;
        if ($domain == 'http://dbmaster.plenty-showcase.de' || $domain == 'http://dbmaster-beta7.plentymarkets.eu' || $domain == 'http://dbmaster-stable7.plentymarkets.eu') {
            $domain = 'https://master.plentymarkets.com';
        }

        return $domain;
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
