<?php

namespace Mondu\PaymentMethods;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\Application;

abstract class GenericMonduPaymentMethod extends PaymentMethodService {
    public function isActive(): bool
    {
        return true;
    }

    public function isSwitchableTo($orderId): bool
    {
        return true;
    }

    public function isSwitchableFrom($orderId)
    {
        return true;
    }

    /**
     * Get the path of the icon.
     */
    public function getIcon(string $lang = "de"): string
    {
        return 'https://checkout.mondu.ai/logo.svg';
    }

    /**
     * Get the name of the payment method.
     */
    public function getName(string $lang = 'de'): string
    {
        return 'Mondu Generic Payment Method name';
    }

    public function getDescription(string $lang = 'de'): string
    {
        return '';
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
     * Return an URL with additional information about the payment method shown in the frontend
     * in the corresponding language.
     */
    public function getSourceUrl(string $lang = 'de'): string
    {
        return 'https://www.mondu.ai/privacy-policy/';
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
        return $this->getIcon();
    }
}
