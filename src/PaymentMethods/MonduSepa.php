<?php

namespace Mondu\PaymentMethods;

use Plenty\Plugin\Translation\Translator;

class MonduSepa extends GenericMonduPaymentMethod
{
    public function getMonduName(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        return $translator->trans('Mondu::PaymentMethods.paymentMethodMonduSepa', [], $lang);
    }

    public function getMonduDescription(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        return $translator->trans('Mondu::PaymentMethods.paymentMethodMonduSepaDescription', [], $lang);
    }

    public function getMonduIdentifier(): string
    {
        return 'direct_debit';
    }
}
