<?php

namespace Mondu\PaymentMethods;

use Plenty\Modules\Payment\Method\Services\PaymentMethodBaseService;

abstract class GenericMonduPaymentMethod extends PaymentMethodBaseService {
    abstract public function getMonduName(string $lang = 'de'): string;

    public function getName(string $lang = ""): string
    {
        return $this->getMonduName($lang);
    }

    public function isActive(): bool
    {
        return true;
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
        return '';
    }

    /**
     * Return a URL with additional information about the payment method shown in the frontend
     * in the corresponding language.
     */
    public function getSourceUrl(string $lang = 'de'): string
    {
        return 'https://www.mondu.ai/privacy-policy/';
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
