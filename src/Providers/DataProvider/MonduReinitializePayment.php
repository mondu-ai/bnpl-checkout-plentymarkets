<?php

namespace Mondu\Providers\DataProvider;

use Plenty\Modules\Payment\Method\Contracts\PaymentMethodRepositoryContract;
use Plenty\Modules\Payment\Method\Models\PaymentMethod;
use Plenty\Plugin\Templates\Twig;
// use PaymentMethod\Helpers\PaymentHelper;

class MonduReinitializePayment
{
  public function call(Twig $twig, $arg):string
  {
    /** @var PaymentMethodRepositoryContract $paymentMethodRepository */
    $paymentMethodRepository = pluginApp(PaymentMethodRepositoryContract::class);
    $paymentMethods          = $paymentMethodRepository->allForPlugin('Mondu');
    $paymentIds              = [];
    foreach ($paymentMethods as $paymentMethod) {
      if ($paymentMethod instanceof PaymentMethod) {
          $paymentIds[] = $paymentMethod->id;
      }
    }

    return $twig->render('Mondu::MonduReinitializePayment', ["order" => $arg[0], "paymentMethodIds" => $paymentIds]);
  }
}
