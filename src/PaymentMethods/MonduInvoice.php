<?php

namespace Mondu\PaymentMethods;

use Plenty\Plugin\Translation\Translator;

class MonduInvoice extends GenericMonduPaymentMethod
{
    public function getMonduName(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        return $translator->trans('Mondu::PaymentMethods.paymentMethodMonduInvoice', [], $lang);
    }

    public function getMonduDescription(string $lang = 'de'): string
    {
        /** @var Translator $translator */
        $translator = pluginApp(Translator::class);

        return $translator->trans('Mondu::PaymentMethods.paymentMethodMonduInvoiceDescription', [], $lang);
    }

    public function getMonduIdentifier(): string
    {
        return 'invoice';
    }
}
