<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\DTO\TransactionDTO;
use App\DTO\DataTableQueryParamsDTO;
use App\Entity\Transaction;
use App\Services\Traits\EmptyLabelsUtilities;
use Clockwork\Clockwork;
use Clockwork\Request\LogLevel;
use DateTime;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TransactionService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly CategoryService               $categoryService,
        private readonly Clockwork                     $clockwork
    ) {
    }

    use EmptyLabelsUtilities;

    public function create(TransactionDTO $transactionDTO, UserInterface $user): Transaction
    {
        $transaction = new Transaction();

        $transaction->setUser($user);

        return $this->update($transaction, $transactionDTO);
    }

    public function update(Transaction $transaction, TransactionDTO $transactionDTO): Transaction
    {
        $transaction->setDescription($transactionDTO->description)
                    ->setDate($transactionDTO->date)
                    ->setAmount($transactionDTO->amount)
                    ->setCategory($transactionDTO->category);


        return $transaction;
    }

    public function getPaginatedData(DataTableQueryParamsDTO $queryParamsDTO): Paginator
    {
        $query = $this->entityManagerService->getRepository(Transaction::class)
                                            ->createQueryBuilder('tr')
                                            ->select('tr', 'c', 'r')
                                            ->leftJoin('tr.category', 'c')
                                            ->leftJoin('tr.receipts', 'r')
                                            ->setFirstResult($queryParamsDTO->start)
                                            ->setMaxResults($queryParamsDTO->length);

        if ($queryParamsDTO->search !== ''){
            $query->where('tr.description LIKE :description')->setParameter(
                'description',
                '%' . addcslashes($queryParamsDTO->search, '%_') . '%'
            );
        }


        // sorting

        $orderBy = in_array($queryParamsDTO->orderBy,
            ['description', 'date', 'amount', 'category']
        ) ? $queryParamsDTO->orderBy : 'date';

        $orderDir = strtolower($queryParamsDTO->orderDir) === 'asc' ? 'asc' : 'desc';

        if ($orderBy === 'category') {
            $query->orderBy('c.name', $orderDir);
        } else {
            $query->orderBy('tr.' . $orderBy, $orderDir);
        }

        return new Paginator($query, fetchJoinCollection: false);
    }

    public function getById(?string $transactionId): ?Transaction
    {
        if ($transactionId === null || ! $transaction = $this->entityManagerService->getRepository(Transaction::class
            )->find($transactionId)) {
            return null;
        }
        return $transaction;
    }


    public function checkDuplicateRow(TransactionDTO $transaction): bool
    {
        return (bool)$this->entityManagerService->getRepository(Transaction::class)->findOneBy([
            'description' => $transaction->description,
            'amount'      => $transaction->amount,
            'category'    => $transaction->category,
            'date'        => $transaction->date,
        ]);
    }

    public function uploadFromFile(string $file, UserInterface $user): void
    {
//        $this->clockwork->log(LogLevel::DEBUG, "Memory usage before: " . memory_get_usage());
//        $this->clockwork->log(LogLevel::DEBUG, "Unit of work: " . $this->entityManagerService->getUnitOfWork()->size());

        $resource = fopen($file, 'r');

        // Avoiding executing multiple find queries
        $categories = $this->categoryService->getAllKeyedByNames($user->getId());

        // periodic flushing
        $count = 0;
        $batchSize = 250;
        $t = 0;
        // skipping the header
        fgetcsv($resource);

        while (($row = fgetcsv($resource)) !== false) {
            $t++;
            if (empty($row)) {
                continue;
            }

            [$date, $description, $categoryName, $amount] = $row;

            $date = new DateTime($date);

            // Maybe batch add categories and only then batch add transactions. ( for categories that don't exist)

            $category = $categories[strtolower($categoryName)] ?? null;

            // create it if it doesn't exist
            if (! $category) {
                $category = $this->categoryService->create(['name' => $categoryName], $user);
                $this->entityManagerService->sync($category);

                $categories[strtolower($categoryName)] = $category;
            }

            $amount = str_replace(['$', ','], '', $amount);

            $transaction = new TransactionDTO(
                $description,
                $date,
                (float)$amount,
                $category
            );

            // Todo: implement batch checks instead for duplicates but let's just assume the user uses some kinda csv template
//            if ($this->checkDuplicateRow($transaction)) {
//                continue;
//            }

            $this->entityManagerService->persist($this->create($transaction, $user));

            if ($count === $batchSize) {
                $this->entityManagerService->sync();
                $this->entityManagerService->clear(Transaction::class);

                $count = 0;
            } else {
                $count++;
            }
            // end while
        }

        if ($count > 0) {
            $this->entityManagerService->sync();
            $this->entityManagerService->clear();
        }

        $this->entityManagerService->clear();

        // logging stuff after the loop
//        $this->clockwork->log(LogLevel::DEBUG, "Memory usage after: " . memory_get_usage());
//        $this->clockwork->log(LogLevel::DEBUG,
//            "Unit of work after: " . $this->entityManagerService->getUnitOfWork()->size()
//        );

        clearstatcache(true);
    }

    public function toggleReviewed(Transaction $transaction): void
    {
        $transaction->setReviewed(! $transaction->isReviewed());
    }
}

