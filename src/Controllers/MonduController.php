<?php

namespace Mondu\Controllers;

use Mondu\Api\ApiClient;
use Mondu\Contracts\MonduTransactionRepositoryContract;
use Mondu\Factories\OrderFactory;
use Mondu\Services\OrderService;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Http\Request;

class MonduController extends Controller
{
    public function confirm(
        FrontendSessionStorageFactoryContract $frontendSessionStorageFactory,
        Response $response,
        Request $request,
        ApiClient $apiClient,
        MonduTransactionRepositoryContract $monduTransactionRepository
    ): \Symfony\Component\HttpFoundation\Response
    {
        $lang = $frontendSessionStorageFactory->getLocaleSettings()->language;
        $monduOrderUuid = $request->get('order_uuid');

        $monduTransactionRepository->createMonduTransaction($monduOrderUuid);

        return $response->redirectTo($lang . '/place-order');
    }

    public function confirmExistingOrder(
        FrontendSessionStorageFactoryContract $frontendSessionStorageFactory,
        Response $response,
        Request $request,
        ApiClient $apiClient,
        MonduTransactionRepositoryContract $monduTransactionRepository,
        OrderService $orderService
    ): \Symfony\Component\HttpFoundation\Response
    {
        $monduTransaction = $monduTransactionRepository->getMonduTransaction();
        $orderId = $monduTransaction->orderId;

        $monduOrderUuid = $request->get('order_uuid');
        $lang = $frontendSessionStorageFactory->getLocaleSettings()->language;

        //TODO validate response
        $apiClient->confirmOrder($monduOrderUuid, ['external_reference_id' => (string) $orderId]);

        $orderService->assignPlentyPaymentToPlentyOrder($orderService->createPaymentObject(6033), $orderId);

        return $response->redirectTo($lang . '/confirmation/' . $orderId);
    }

    public function reInit(
        Response $response,
        Request $request,
        OrderFactory $orderFactory,
        ApiClient $apiClient,
        MonduTransactionRepositoryContract $monduTransactionRepository,
        FrontendSessionStorageFactoryContract $frontendSessionStorageFactory
    ): \Symfony\Component\HttpFoundation\Response
    {
        $orderId = $request->get('order_id');
        $mopId = $request->get('mop_id');
        $lang = $frontendSessionStorageFactory->getLocaleSettings()->language;

        $data = $apiClient->createOrder($orderFactory->buildOrder($mopId, $lang, $orderId));

        $monduTransactionRepository->createMonduTransaction($data['order']['uuid']);
        $monduTransactionRepository->setOrderId($orderId);

        return $response->json(['url' => $data['order']['hosted_checkout_url']]);
    }
}
