<?php

namespace Mondu\PaymentMethods;

use Plenty\Plugin\Translation\Translator;

class MonduInstallment extends GenericMonduPaymentMethod
{
    public function getMonduName(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        return $translator->trans('Mondu::PaymentMethods.paymentMethodMonduInstallment', [], $lang);
    }

    public function getMonduDescription(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        return $translator->trans('Mondu::PaymentMethods.paymentMethodMonduInstallmentDescription', [], $lang);
    }

    public function getMonduIdentifier(): string
    {
        return 'installment';
    }
}
