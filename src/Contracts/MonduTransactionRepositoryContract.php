<?php
namespace Mondu\Contracts;

use Mondu\Models\MonduTransaction;
use Plenty\Modules\Plugin\DataBase\Contracts\Model;

interface MonduTransactionRepositoryContract
{
    public function createMonduTransaction(string $monduOrderUuid): MonduTransaction;

    public function setOrderId(int $orderId);

    public function setMonduOrderuuid(string $monduOrderUuid);

    /**
     * @return MonduTransaction|null
     */
    public function getMonduTransaction();

    public function getMonduTransactionId(): int;

    public function getAllTransactions(): array;
}
