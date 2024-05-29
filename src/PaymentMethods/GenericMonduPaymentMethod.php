<?php

namespace Mondu\PaymentMethods;

use Mondu\Services\SettingsService;
use Plenty\Modules\Frontend\Contracts\Checkout;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Payment\Method\Services\PaymentMethodBaseService;
use Plenty\Plugin\Log\Loggable;

abstract class GenericMonduPaymentMethod extends PaymentMethodBaseService {
    use Loggable;
    /**
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * @var Checkout
     */
    protected $checkout;

    /**
     * @var CountryRepositoryContract
     */
    protected $countryRepositoryContract;

    abstract public function getMonduName(string $lang = 'de'): string;
    abstract public function getMonduDescription(string $lang = 'de'): string;
    abstract public function getMonduIdentifier(): string;

    public function __construct(
        SettingsService $settingsService,
        Checkout $checkout,
        CountryRepositoryContract $countryRepositoryContract
    )
    {
        $this->settingsService = $settingsService;
        $this->checkout = $checkout;
        $this->countryRepositoryContract = $countryRepositoryContract;
    }

    public function getName(string $lang = ""): string
    {
        return $this->getMonduName($lang);
    }

    public function isActive(): bool
    {
        $country = $this->countryRepositoryContract->getCountryById($this->checkout->getShippingCountryId());

        $countries = $this->settingsService->getSetting('shippingCountries');
        $allowedPaymentMethods = $this->settingsService->getSetting('paymentMethods');

        if(!is_array($allowedPaymentMethods) || !in_array($this->getMonduIdentifier(), $allowedPaymentMethods)) return false;

        if(is_array($countries) && in_array($country->isoCode2, $countries)) return true;

        return false;
    }

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

    public function getDescription(string $lang = 'de'): string
    {
        return $this->getMonduDescription($lang);
    }

    /**
     * Return a URL with additional information about the payment method shown in the frontend
     * in the corresponding language.
     */
    public function getSourceUrl(string $lang = 'de'): string
    {
        $country = $this->countryRepositoryContract->getCountryById($this->checkout->getShippingCountryId());

        if($country->isoCode2 === 'GB') {
            return "https://www.mondu.ai/en-gb/gdpr-notification-for-buyers/";
        }

        if ($lang === 'en') {
            return 'https://www.mondu.ai/gdpr-notification-for-buyers/';
        }

        return "https://mondu.ai/{$lang}/gdpr-notification-for-buyers";
    }

    public function isSwitchableTo(): bool
    {
        return true;
    }

    public function isSwitchableFrom(): bool
    {
        return true;
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
