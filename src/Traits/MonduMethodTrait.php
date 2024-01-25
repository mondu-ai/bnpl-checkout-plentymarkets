<?php

namespace Mondu\Traits;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;

trait MonduMethodTrait
{
    private function getMonduPaymentMethod(int $paymentMethodId): ?PaymentMethod
    {
        /** @var PaymentMethodRepositoryContract $paymentMethodRepository */
        $paymentMethodRepository = pluginApp(PaymentMethodRepositoryContract::class);
        $paymentMethods          = $paymentMethodRepository->allForPlugin('Mondu');
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod instanceof PaymentMethod) {
                if ($paymentMethod->id == $paymentMethodId) {
                    return $paymentMethod;
                }
            }
        }
        return null;
    }

    private function getMonduPaymentMethodName(int $paymentMethodId): string
    {
        $mapping = [
            'MonduInvoice' => 'invoice',
            'MonduSepa' => 'direct_debit',
            'MonduInstallment' => 'installment'
        ];

        $paymentMethod = $this->getMonduPaymentMethod($paymentMethodId);

        return $mapping[$paymentMethod->paymentKey] ?? 'invoice';
    }

    private function getMopIdFromMonduName(string $monduPaymentMethod): ?int
    {
        $mapping = [
            'invoice' => 'MonduInvoice',
            'direct_debit' => 'MonduSepa',
            'installment' => 'MonduInstallment'
        ];

        /** @var PaymentMethodRepositoryContract $paymentMethodRepository */
        $paymentMethodRepository = pluginApp(PaymentMethodRepositoryContract::class);
        $paymentMethods          = $paymentMethodRepository->allForPlugin('Mondu');
        foreach ($paymentMethods as $paymentMethod) {
            if ($paymentMethod instanceof PaymentMethod) {
                if (isset($mapping[$monduPaymentMethod]) && $paymentMethod->paymentKey == $mapping[$monduPaymentMethod]) {
                    return $paymentMethod->id;
                }
            }
        }

        return null;
    }
}
