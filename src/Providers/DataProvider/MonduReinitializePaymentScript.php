<?php

namespace Mondu\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;

class MonduReinitializePaymentScript
{
  public function call(Twig $twig, $arg):string
  {
    return $twig->render('Mondu::MonduReinitializePaymentScript', ["order" => $arg[0]]);
  }
}
