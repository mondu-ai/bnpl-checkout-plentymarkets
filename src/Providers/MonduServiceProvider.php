<?php

namespace Mondu\Providers;

use Mondu\Api\ApiClient;
use Mondu\Contracts\MonduTransactionRepositoryContract;
use Mondu\Events\OrderPdfGeneration;
use Mondu\PaymentMethods\MonduInstallment;
use Mondu\Procedures\OrderShipped;
use Mondu\Procedures\RefundOrder;
use Mondu\Repositories\MonduTransactionRepository;
use Mondu\Services\OrderService;
use Mondu\Services\SettingsService;
use Plenty\Modules\EventProcedures\Services\Entries\TriggerEntry;
use Plenty\Modules\Frontend\Events\FrontendLanguageChanged;
use Plenty\Modules\Frontend\Events\FrontendShippingCountryChanged;
use Plenty\Modules\Order\Pdf\Events\OrderPdfGenerationEvent;
use Plenty\Plugin\ServiceProvider;

use Mondu\PaymentMethods\MonduInvoice;
use Mondu\PaymentMethods\MonduSepa;
use Plenty\Modules\Basket\Events\Basket\AfterBasketCreate;
use Plenty\Modules\Basket\Events\Basket\AfterBasketChanged;
use Plenty\Modules\Basket\Events\BasketItem\AfterBasketItemAdd;
use Plenty\Modules\Wizard\Contracts\WizardContainerContract;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodContainer;
use Mondu\Assistants\MonduAssistant;
use Mondu\Events\ExecuteMonduPayment;
use Mondu\Events\PrepareMonduPayment;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Events\Dispatcher;
use Plenty\Modules\Payment\Events\Checkout\GetPaymentMethodContent;
use Plenty\Modules\Payment\Events\Checkout\ExecutePayment;
use Plenty\Modules\EventProcedures\Services\EventProceduresService;
use Plenty\Modules\EventProcedures\Services\Entries\ProcedureEntry;
use Mondu\Procedures\OrderCanceled;

class MonduServiceProvider extends ServiceProvider
{
    use Loggable;

    /**
    * Register the route service provider
    */
    public function register()
    {
        $this->getApplication()->register(MonduRouteServiceProvider::class);
        $this->getApplication()->singleton(ApiClient::class);
        $this->getApplication()->singleton(OrderService::class);
        $this->getApplication()->singleton(SettingsService::class);
        $this->getApplication()->bind(MonduTransactionRepositoryContract::class, MonduTransactionRepository::class);
    }

    public function boot(PaymentMethodContainer $payContainer, Dispatcher $dispatcher)
    {
        pluginApp(WizardContainerContract::class)->register('payment-mondu-assistant', MonduAssistant::class);


        $payContainer->register('Mondu::MonduInvoice', MonduInvoice::class,
            [
                AfterBasketChanged::class,
                AfterBasketItemAdd::class,
                AfterBasketCreate::class,
                FrontendLanguageChanged::class,
                FrontendShippingCountryChanged::class
            ]
        );

        $payContainer->register('Mondu::MonduSepa', MonduSepa::class,
            [
                AfterBasketChanged::class,
                AfterBasketItemAdd::class,
                AfterBasketCreate::class,
                FrontendLanguageChanged::class,
                FrontendShippingCountryChanged::class
            ]
        );

        $payContainer->register('Mondu::MonduInstallment', MonduInstallment::class,
            [
                AfterBasketChanged::class,
                AfterBasketItemAdd::class,
                AfterBasketCreate::class,
                FrontendLanguageChanged::class,
                FrontendShippingCountryChanged::class
            ]
        );

        $dispatcher->listen(GetPaymentMethodContent::class, PrepareMonduPayment::class);
        $dispatcher->listen(ExecutePayment::class, ExecuteMonduPayment::class);

        $this->bootEventProcedures();
    }

    private function bootEventProcedures()
    {
        $eventProceduresService = pluginApp(EventProceduresService::class);

        $eventProceduresService->registerProcedure(
            'Mondu',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Mondu: Cancel Order',
                'en' => 'Mondu: Cancel Order'
            ],
            OrderCanceled::class . '@run'
        );

        $eventProceduresService->registerProcedure(
            'Mondu',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Mondu: Create Invoice',
                'en' => 'Mondu: Create Invoice'
            ],
            OrderShipped::class . '@run'
        );

        $eventProceduresService->registerProcedure(
            'Mondu',
            ProcedureEntry::EVENT_TYPE_ORDER,
            [
                'de' => 'Mondu: Create Credit Note',
                'en' => 'Mondu: Create Credit Note'
            ],
            RefundOrder::class . '@run'
        );

        // $this->getLogger('MonduServiceProvider::boot')->error('EventProceduresService: ');

        // $eventProceduresService->registerProcedure(
        //     'Mondu',
        //     ProcedureEntry::EVENT_TYPE_ORDER,
        //     [
        //         'de' => 'Register shipment at Mondu',
        //         'en' => 'Register shipment at Mondu'
        //     ]
        // );
    }
}
