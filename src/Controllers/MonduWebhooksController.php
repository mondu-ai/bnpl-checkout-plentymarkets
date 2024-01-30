<?php

namespace Mondu\Controllers;

use Mondu\Services\SettingsService;
use Mondu\Traits\MonduCommentTrait;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Models\Order;
use Plenty\Modules\Payment\Contracts\PaymentOrderRelationRepositoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Payment\Models\Payment;
use Plenty\Modules\Payment\Models\PaymentProperty;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;
use Plenty\Plugin\Log\Loggable;

class MonduWebhooksController extends Controller
{
    use Loggable, MonduCommentTrait;

    public function webhooks(
        Request $request,
        SettingsService $settings,
        Response $response
    ): \Symfony\Component\HttpFoundation\Response
    {
        $data = $request->except(['plentyMarkets', 'pluginSetPreview']);

        // hack because $request->all() and $request->except() escapes forward slashes
        $content = str_replace('\\', '', json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $signature = $request->header('X-Mondu-Signature', '');

        $isSignatureValid = $this->isSignatureValid($content, $settings->getSetting('webhookSecret'), $signature);

        if (!$isSignatureValid) {
            return $response->json(['message' => 'Signature Invalid'], 401);
        }

        $data = $request->all();
        $topic = $data['topic'];

        switch($topic) {
            case 'order/confirmed':
                [$responseData, $responseStatus] = $this->handleConfirmed($data);
                break;
            case 'order/declined':
                [$responseData, $responseStatus] = $this->handleDeclined($data);
                break;
            default:
                $responseData = ['message' => 'Unregistered topic'];
                $responseStatus = 200;
        }

        return $response->json($responseData, $responseStatus);
    }

    private function handleConfirmed(array $data): array
    {
        [$responseData, $responseStatus, $orderId] = $this->updateOrderPaymentStatus($data, Payment::STATUS_APPROVED);

        if ($responseStatus === 200) {
            $this->addOrderComments($orderId, 'orderConfirmedWebhook');
        }

        return [$responseData, $responseStatus];
    }

    private function handleDeclined(array $data): array
    {
        [$responseData, $responseStatus, $orderId] = $this->updateOrderPaymentStatus($data, Payment::STATUS_CANCELED);

        if ($responseStatus === 200) {
            $this->addOrderComments($orderId, 'orderDeclinedWebhook');
        }

        return [$responseData, $responseStatus];
    }

    private function isSignatureValid($content, $secret, $signature): bool
    {
        return hash_hmac('sha256', $content, $secret) === $signature;
    }

    private function updateOrderPaymentStatus(array $data, $status): array
    {
        /** @var OrderRepositoryContract $orderRepository */
        $orderRepository = pluginApp(OrderRepositoryContract::class);

        /** @var PaymentRepositoryContract $paymentRepository */
        $paymentRepository = pluginApp(PaymentRepositoryContract::class);

        /** @var AuthHelper $authHelper */
        $authHelper = pluginApp(AuthHelper::class);

        /** @var Order $order */
        $order = $authHelper->processUnguarded(function() use($orderRepository, $data) {
            return $orderRepository->findOrderByExternalOrderId($data['order_uuid']);
        });

        if(!$order) {
            return [['message' => 'Order not found'], 404, null];
        }

        /** @var Payment[] $payments */
        $payments = $authHelper->processUnguarded(function() use($paymentRepository, $data) {
            return $paymentRepository->getPaymentsByPropertyTypeAndValue(PaymentProperty::TYPE_TRANSACTION_ID, $data['order_uuid']);
        });

        foreach ($payments as $payment) {
            $payment->status = $status;
            $payment->regenerateHash = true;
            $paymentRepository->updatePayment($payment);
        }

        return [['message' => 'ok'], 200, $order->id];
    }
}