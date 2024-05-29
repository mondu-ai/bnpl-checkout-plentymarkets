<?php

namespace Mondu\Migrations;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;

class CreatePaymentMethods_01
{
    /**
     * @var PaymentMethodRepositoryContract
     */
    private $paymentMethodRepositoryContract;

    /**
     * CreatePaymentMethod constructor.
     */
    public function __construct(PaymentMethodRepositoryContract $paymentMethodRepositoryContract)
    {
        $this->paymentMethodRepositoryContract = $paymentMethodRepositoryContract;
    }

    /**
     * The run method will register the payment method when the migration runs.
     */
    public function run()
    {
        $this->paymentMethodRepositoryContract->createPaymentMethod([
            'pluginKey' => 'Mondu', // Unique key for the plugin
            'paymentKey' => 'MonduInvoice', // Unique key for the payment method
            'name' => 'Mondu Rechnungskauf - jetzt kaufen, später bezahlen' // Default name for the payment method
        ]);
        $this->paymentMethodRepositoryContract->createPaymentMethod([
            'pluginKey' => 'Mondu',
            'paymentKey' => 'MonduSepa',
            'name' => 'Mondu SEPA-Lastschrift - jetzt kaufen, später per Bankeinzug bezahlen'
        ]);
        $this->paymentMethodRepositoryContract->createPaymentMethod([
            'pluginKey' => 'Mondu',
            'paymentKey' => 'MonduInstallment',
            'name' => 'Mondu Ratenzahlung - Bequem in Raten per Bankeinzug zahlen'
        ]);
    }
}
