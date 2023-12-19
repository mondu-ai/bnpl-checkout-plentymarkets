<?php
namespace Mondu\Repositories;

use Mondu\Contracts\MonduTransactionRepositoryContract;
use Mondu\Models\MonduTransaction;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Plugin\DataBase\Contracts\DataBase;

class MonduTransactionRepository implements MonduTransactionRepositoryContract
{
    public const SESSION_KEY = 'Mondu_TransactionId';

    /**
     * @var DataBase
     */
    private $dataBase;

    /**
     * @var FrontendSessionStorageFactoryContract
     */
    private $frontendSessionStorageFactory;

    public function __construct(
        DataBase $dataBase,
        FrontendSessionStorageFactoryContract $frontendSessionStorageFactory
    ) {
        $this->dataBase = $dataBase;
        $this->frontendSessionStorageFactory = $frontendSessionStorageFactory;
    }

    public function createMonduTransaction(string $monduOrderUuid): MonduTransaction
    {
        /** @var MonduTransaction $monduTransaction */
        $monduTransaction = pluginApp(MonduTransaction::class);

        $monduTransaction->timestamp = time();
        $monduTransaction->monduOrderUuid = $monduOrderUuid;
        $monduTransaction = $this->dataBase->save($monduTransaction);
        if ($monduTransaction instanceof MonduTransaction) {
            $this->frontendSessionStorageFactory->getPlugin()->setValue(self::SESSION_KEY, $monduTransaction->id);
        }
        return $monduTransaction;
    }

    public function getMonduTransaction()
    {
        return $this->dataBase->find(MonduTransaction::class, $this->getMonduTransactionId());
    }

    public function getMonduTransactionByUuid(string $uuid)
    {
        return $this->dataBase->find(MonduTransaction::class, $uuid);
    }

    public function getMonduTransactionId(): int
    {
        return $this->frontendSessionStorageFactory->getPlugin()->getValue(self::SESSION_KEY);
    }

    public function setOrderId(int $orderId)
    {
        $transaction = $this->getMonduTransaction();

        if ($transaction instanceof MonduTransaction) {
            $transaction->orderId = $orderId;
            $this->dataBase->save($transaction);
        }
    }

    public function setMonduOrderUuid(string $monduOrderUuid)
    {
        $transaction = $this->getMonduTransaction();

        if ($transaction instanceof MonduTransaction) {
            $transaction->monduOrderUuid = $monduOrderUuid;
            $this->dataBase->save($transaction);
        }
    }

    public function getAllTransactions(): array
    {
        return $this->dataBase->query(MonduTransaction::class)->where('id', '>', 0)->get();
    }
}
