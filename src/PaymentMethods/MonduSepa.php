<?php

namespace Mondu\PaymentMethods;

use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Payment\Method\Services\PaymentMethodBaseService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Application;

/**
 * Class PaymentMethod
 * @package Mondu\PaymentMethods
 */
class MonduSepa extends PaymentMethodBaseService
{
    use Loggable;

    /** @var BasketRepositoryContract */
    private $basketRepo;

    public function __construct(
        BasketRepositoryContract $basketRepo
    ) {
        $this->basketRepo = $basketRepo;
    }

    /**
     * Check if the payment method is active.
     * Return true if the payment method is active, if not return false.
     */
    public function isActive(): bool
    {
        //TODO
        /**
         * In our assistant, we let the user decide in which shipping countries the payment method
         * is allowed, therefore we have to check it here.
         */
        // if (!in_array($this->checkout->getShippingCountryId(), $this->settings->getShippingCountries())) {
        //     return false;
        // }

        return true;
    }

    /**
     * Get the name of the payment method.
     */
    public function getName(string $lang = 'de'): string
    {
        return 'Mondu SEPA-Lastschrift - jetzt kaufen, später per Bankeinzug bezahlen';
    }

    /**
     * Return an additional payment fee for the payment method.
     */
    public function getFee(): float
    {
        return 0.00;
    }

    /**
     * Get the path of the icon.
     */
    public function getIcon(string $lang = "de"): string
    {
        return 'https://checkout.mondu.ai/logo.svg';
    }

    /**
     * Get the description of the payment method.
     */
    public function getDescription(string $lang = 'de'): string
    {
        return '';
    }

    /**
     * Return an URL with additional information about the payment method shown in the frontend
     * in the corresponding language.
     */
    public function getSourceUrl(string $lang = 'de'): string
    {
        return 'https://www.mondu.ai/privacy-policy/';
    }

    /**
     * Check if it is allowed to switch to this payment method after the order has been placed.
     */
    public function isSwitchableTo(): bool
    {
        return false;
    }

    /**
     * Check if it is allowed to switch from this payment method to another after the order has been placed.
     */
    public function isSwitchableFrom(): bool
    {
        return false;
    }

    /**
     * Check if this payment method should be searchable in the back end.
     */
    public function isBackendSearchable(): bool
    {
        return true;
    }

    /**
     * Check if this payment method should be active in the back end.
     */
    public function isBackendActive(): bool
    {
        return true;
    }

    /**
     * Get the name for the back end.
     */
    public function getBackendName(string $lang = 'de'): string
    {
        return $this->getName($lang);
    }

    /**
     * Check if this payment method can handle subscriptions.
     */
    public function canHandleSubscriptions(): bool
    {
        return false;
    }

    /**
     * Return the icon for the back end, shown in the payments UI.
     */
    public function getBackendIcon(): string
    {
        return 'https://checkout.mondu.ai/logo.svg';
    }
}
