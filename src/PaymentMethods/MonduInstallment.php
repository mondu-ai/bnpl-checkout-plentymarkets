<?php

namespace Mondu\PaymentMethods;

class MonduInstallment extends GenericMonduPaymentMethod
{
    public function getMonduName(string $lang = 'de'): string
    {
        return 'Mondu Ratenzahlung - Bequem in Raten per Bankeinzug zahlen';
    }
}
