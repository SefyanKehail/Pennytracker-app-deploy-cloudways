<?php

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\Services\ReceiptService;
use App\Services\TransactionService;
use App\Validators\ReceiptFileValidator;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Ramsey\Uuid\Uuid;
use Slim\Psr7\Stream;


class ReceiptsController
{
    public function __construct(
        private readonly ValidatorFactoryInterface     $validatorFactory,
        private readonly Filesystem                    $filesystem,
        private readonly ReceiptService                $receiptService,
        private readonly EntityManagerServiceInterface $entityManagerService
    ) {
    }

    public function store(Request $request, Response $response, Transaction $transaction): Response
    {
        // do the math
        /***@var UploadedFileInterface $file */

        $files           = $this->validatorFactory->make(ReceiptFileValidator::class
        )->validate($request->getUploadedFiles());
        $file            = $files['receipt'];
        $filename        = $file->getClientFilename();
        $storageFilename = Uuid::uuid4()->toString();
        $mediaType       = $file->getClientMediaType();

        $receipt = $this->receiptService->create(
            transaction: $transaction,
            filename: $filename,
            storageFilename: $storageFilename,
            mediaType: $mediaType
        );

        $this->entityManagerService->sync($receipt);


        // writeStream for large files
        $this->filesystem->write(
            'receipts' . DIRECTORY_SEPARATOR . $storageFilename,
            $file->getStream()->getContents()
        );

        $file->getStream()->rewind();

        return $response;
    }


    public function download(Response $response, Transaction $transaction, Receipt $receipt): Response
    {
        // if somehow the receipt doesn't belong to the transaction

        if ($receipt->getTransaction()->getId() !== $transaction->getId()) {
            return $response->withStatus(401);
        }

        // Todo: in case download failed
        try {
            $file = $this->filesystem->readStream('receipts/' . $receipt->getStorageFilename());
        } catch (FilesystemException $filesystemException) {
            var_dump($filesystemException->getMessage());
        }


        $response = $response->withHeader('Content-Disposition', 'inline; filename="' . $receipt->getFilename() . '"')
                             ->withHeader('Content-Type', $receipt->getMediaType());


        return $response->withBody(new Stream($file));
    }


    public function delete(Response $response, Transaction $transaction, Receipt $receipt): Response
    {
        // if somehow the receipt doesn't belong to the transaction ( can be done through policies instead of manually doing it here)

        if ($receipt->getTransaction()->getId() !== $transaction->getId()) {
            return $response->withStatus(401);
        }

        $this->entityManagerService->delete($receipt, true);

        return $response;
    }


}