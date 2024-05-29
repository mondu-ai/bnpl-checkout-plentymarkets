<?php

namespace Mondu\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

class MonduRouteServiceProvider extends RouteServiceProvider
{
    public function map(Router $router)
    {
        $router->get('mondu/confirm','Mondu\Controllers\MonduController@confirm');
        $router->get('mondu/confirm_existing','Mondu\Controllers\MonduController@confirmExistingOrder');
        $router->get('mondu/cancel','Mondu\Controllers\MonduController@cancel');
        $router->get('mondu/init_payment','Mondu\Controllers\MonduController@reInit');
        $router->get('mondu/invoice','Mondu\Controllers\MonduController@getInvoice');

        $router->post('mondu/webhook','Mondu\Controllers\MonduWebhooksController@webhooks');
    }
}
