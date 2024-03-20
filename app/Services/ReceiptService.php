<?php

namespace App\Services;

use App\Contracts\EntityManagerServiceInterface;
use App\Entity\Receipt;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;

class ReceiptService
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManagerService)
    {
    }

    public function create(
        Transaction $transaction,
        string      $filename,
        string      $storageFilename,
        string      $mediaType
    ): Receipt {
        $receipt = new Receipt();

        $receipt->setTransaction($transaction);
        $receipt->setFilename($filename);
        $receipt->setStorageFilename($storageFilename);
        $receipt->setCreatedAt(new \DateTime());
        $receipt->setMediaType($mediaType);

        return $receipt;
    }


    public function getById(?string $receiptId): ?Receipt
    {
        if ($receiptId === null || ! $receipt = $this->entityManagerService->getRepository(Receipt::class)->find($receiptId)) {
            return null;
        }
        return $receipt;
    }
}