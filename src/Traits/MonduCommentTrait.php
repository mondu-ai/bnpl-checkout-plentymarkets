<?php

namespace Mondu\Traits;

use Mondu\Services\SettingsService;
use Plenty\Modules\Authorization\Services\AuthHelper;
use Plenty\Modules\Comment\Contracts\CommentRepositoryContract;
use Plenty\Modules\User\Contracts\UserRepositoryContract;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Translation\Translator;

trait MonduCommentTrait
{
    use Loggable;

    private function addOrderComments($orderId, $msg)
    {
        /** @var SettingsService $settingsService */
        $settingsService = pluginApp(SettingsService::class);

        $commentToggle = $settingsService->getSetting('backendUserToggle');
        $userId = $settingsService->getSetting('backendUserId');

        if ($commentToggle && $userId) {
            /** @var UserRepositoryContract $userRepository */
            $userRepository = pluginApp(UserRepositoryContract::class);
            /** @var  AuthHelper $authHelper */
            $authHelper = pluginApp(AuthHelper::class);

            $lang = $authHelper->processUnguarded(
                function () use ($userRepository, $userId) {
                    $backendUser = $userRepository->getUserById($userId);
                    return $backendUser->lang;
                }
            );

            /** @var Translator $translator */
            $translator = pluginApp(Translator::class);
            $msg = $translator->trans('Mondu::Procedures.' . $msg, [], $lang);
            $commentData = [];

            $commentData['referenceType'] = 'order';
            $commentData['referenceValue'] = $orderId;

            $commentData['text'] = '<b>Mondu</b> : ' . $msg . '<br>';
            $commentData['isVisibleForContact'] = false;
            $commentData['userId'] = (int) $userId;

            try {
                $authHelper->processUnguarded(
                    function () use ($commentData) {
                        /** @var CommentRepositoryContract $commentRepo */
                        $commentRepo = pluginApp(CommentRepositoryContract::class);
                        //unguarded
                        $commentRepo->createComment($commentData);
                    }
                );
            } catch (\Exception $e) {
                $this->getLogger(__CLASS__.'::'.__FUNCTION__)
                    ->error("Mondu::Logs.monduComment",[
                        'user_id' => $userId
                    ]);
            }
        }
    }
}
