<?php declare(strict_types=1);

namespace Satbased\Accounting\ReadModel\Standard;

use Daikon\Entity\EntityInterface;
use Daikon\ReadModel\Projection\ProjectionInterface;
use Daikon\ReadModel\Query\QueryInterface;
use Daikon\ReadModel\Repository\RepositoryInterface;
use Daikon\ReadModel\Storage\SearchAdapterInterface;
use Daikon\ReadModel\Storage\ScrollAdapterInterface;
use Daikon\ReadModel\Storage\StorageAdapterInterface;
use Daikon\ReadModel\Storage\StorageResultInterface;

final class PaymentRepository implements RepositoryInterface
{
    /** @var StorageAdapterInterface|SearchAdapterInterface|ScrollAdapterInterface */
    private $storageAdapter;

    public function __construct(StorageAdapterInterface $storageAdapter)
    {
        $this->storageAdapter = $storageAdapter;
    }

    public function findById(string $identifier): StorageResultInterface
    {
        return $this->storageAdapter->read($identifier);
    }

    public function findByIds(array $identifiers): StorageResultInterface
    {
    }

    public function search(QueryInterface $query, int $from = null, int $size = null): StorageResultInterface
    {
        return $this->storageAdapter->search($query, $from, $size);
    }

    /** @param Payment $payment */
    public function persist(ProjectionInterface $payment): bool
    {
        return $this->storageAdapter->write(
            (string)$payment->getPaymentId(),
            (array)$payment->toNative()
        );
    }

    public function walk(QueryInterface $query, callable $callback, int $size = null): void
    {
        $storageResult = $this->storageAdapter->scrollStart($query, $size);
        $projections = $storageResult->getProjectionMap();
        $cursor = $storageResult->getMetadata()->get('cursor');

        do {
            array_walk($projections->unwrap(), $callback);
            $storageResult = $this->storageAdapter->scrollNext($cursor);
            $projections = $storageResult->getProjectionMap();
            $cursor = $storageResult->getMetadata()->get('cursor');
        } while (!$projections->isEmpty());

        $this->storageAdapter->scrollEnd($cursor);
    }

    public function makeProjection(): Payment
    {
        return Payment::fromNative([EntityInterface::TYPE_KEY => Payment::class]);
    }
}
