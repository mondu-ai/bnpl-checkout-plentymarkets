<?php

namespace Mondu\Controllers;

use Mondu\Api\ApiClient;
use Mondu\Contracts\MonduTransactionRepositoryContract;
use Mondu\Factories\InvoiceFactory;
use Mondu\Factories\OrderFactory;
use Mondu\Services\OrderService;
use Mondu\Services\SettingsService;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Document\Models\Document;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Documents\Contracts\OrderDocumentStorageContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Response;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Plugin\Libs\Contracts\LibraryCallContract;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Templates\Twig;
use Plenty\Plugin\Http\Request;

class MonduController extends Controller
{
    use Loggable;

    public function cancel(
        FrontendSessionStorageFactoryContract $frontendSessionStorageFactory,
        Response $response,
        Request $request
    ): \Symfony\Component\HttpFoundation\Response
    {
        $lang = $frontendSessionStorageFactory->getLocaleSettings()->language;
        $orderId = $request->get('order_id');

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.orderCanceled", [
                'order_id' => (string) $orderId,
                'lang' => $lang
            ]);

        if ($orderId) {
            return $response->redirectTo($lang . '/confirmation/' . $orderId);
        }

        return $response->redirectTo($lang. '/checkout');
    }

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

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.confirmOrder", [
                'mondu_uuid' => (string) $monduOrderUuid,
                'flow' => 'Checkout flow'
            ]);

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

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.confirmOrder", [
                'order_id' => (string) $orderId,
                'mondu_uuid' => (string) $monduOrderUuid,
                'flow' => 'Existing order flow'
            ]);

        $lang = $frontendSessionStorageFactory->getLocaleSettings()->language;

        $data = $apiClient->confirmOrder($monduOrderUuid, ['external_reference_id' => (string) $orderId]);

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.confirmOrder", [
                'confirm_order_data' => $data,
                'flow' => 'Existing order flow'
            ]);

        $orderService->assignPlentyPaymentToPlentyOrder($orderService->createPaymentObject(6033), $orderId, $monduOrderUuid);

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

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.createOrder", [
                'order_id' => (string) $orderId,
                'mop_id' => (string) $mopId,
                'lang' => (string) $lang,
                'flow' => 'Existing order flow'
            ]);

        $data = $apiClient->createOrder($orderFactory->buildOrder($mopId, $lang, $orderId));

        $this->getLogger(__CLASS__.'::'.__FUNCTION__)
            ->info("Mondu::Logs.createOrder", [
                'create_order_data' => $data,
                'flow' => 'Existing order flow'
            ]);

        $monduTransactionRepository->createMonduTransaction($data['order']['uuid']);
        $monduTransactionRepository->setOrderId($orderId);

        return $response->json(['url' => $data['order']['hosted_checkout_url']]);
    }

    public function getInvoice(
        Request $request,
        Response $response,
        MonduTransactionRepositoryContract $monduTransactionRepository,
        OrderRepositoryContract $orderRepository,
        OrderDocumentStorageContract $orderDocumentStorage
    ): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $monduTransaction = $monduTransactionRepository->getMonduTransactionByUuid($request->get('order_uuid'));

            if(!$monduTransaction) {
                return $response->json(['error' => 'Not found'], 404);
            }

            $this->getLogger(__CLASS__.'::'.__FUNCTION__ . 'Info')
                ->info("Mondu::Logs.getInvoice",[
                    'order_id' => $monduTransaction->orderId,
                    'uuid' => $monduTransaction->monduOrderUuid
                ]);

            /** @var AuthHelper $authHelper */
            $authHelper = pluginApp(AuthHelper::class);

            $order = $authHelper->processUnguarded(function() use($orderRepository, $monduTransaction) {
                return $orderRepository->findOrderById($monduTransaction->orderId);
            });

            $documents = $order->documents;

            $invoiceDoc = null;

            foreach ($documents as $document) {
                if ($document->type === Document::INVOICE) {
                    $invoiceDoc = $orderDocumentStorage->get($document->path);
                }
            }

            if (!$invoiceDoc) {
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->info("Mondu::Logs.getInvoice",[
                        'info' => 'Invoice Document Not found'
                    ]);
                return $response->json(['error' => 'Not found'], 404);
            }

            return $response->make($invoiceDoc->body, 200, [
                'Content-Type' => 'application/pdf'
            ]);
        } catch(\Throwable $e) {
            $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                ->error("Mondu::Logs.getInvoice",[
                    'error' => $e->getMessage(),
                    'trace' => $e->getTrace()
                ]);
            return $response->json(['error' => 'true']);
        }
    }
}
