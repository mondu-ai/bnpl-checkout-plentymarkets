<?php

namespace Mondu\PaymentMethods;

class MonduInvoice extends GenericMonduPaymentMethod
{
    public function getMonduName(string $lang = 'de'): string
    {
        return 'Mondu Rechnungskauf - jetzt kaufen, später bezahlen';
    }

    public function getMonduIdentifier(): string
    {
        return 'invoice';
    }
}
