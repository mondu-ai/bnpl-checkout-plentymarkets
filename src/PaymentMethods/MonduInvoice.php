<?php

namespace Mondu\PaymentMethods;

class MonduInvoice extends GenericMonduPaymentMethod
{
    public function getName(string $lang = 'de'): string
    {
        return 'Mondu Rechnungskauf - jetzt kaufen, später bezahlen';
    }
}