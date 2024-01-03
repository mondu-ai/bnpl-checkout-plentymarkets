<?php

namespace Mondu\Controllers;

use Mondu\Services\SettingsService;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;

class MonduWebhooksController extends Controller
{
    use Loggable;

    public function webhooks(
        Request $request,
        SettingsService $settings,
        Response $response
    ) {
        $data = json_encode($request->all());
        $webhookSecret = $settings->getSetting('webhookSecret');

        $this->handleConfirmed();

        return $response->json(['status' => 200]);
    }

    private function handleConfirmed()
    {
        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info('Mondu::Logs.webhookConfirmed',[
                'info' => 'TODO'
            ]);
    }
}