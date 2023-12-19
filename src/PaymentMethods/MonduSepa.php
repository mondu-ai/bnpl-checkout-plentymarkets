<?php

namespace Mondu\PaymentMethods;

class MonduSepa extends GenericMonduPaymentMethod
{
    public function getName(string $lang = 'de'): string
    {
        return 'Mondu SEPA-Lastschrift - jetzt kaufen, später per Bankeinzug bezahlen';
    }
}
