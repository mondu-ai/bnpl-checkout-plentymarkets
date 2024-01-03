<?php

namespace Mondu\PaymentMethods;

class MonduSepa extends GenericMonduPaymentMethod
{
    public function getMonduName(string $lang = 'de'): string
    {
        return 'Mondu SEPA-Lastschrift - jetzt kaufen, später per Bankeinzug bezahlen';
    }
}
