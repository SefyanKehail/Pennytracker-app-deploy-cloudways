<?php

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\DTO\TransactionDTO;
use App\Entity\Receipt;
use App\Entity\Transaction;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use App\Services\TransactionService;
use App\Validators\TransactionFileValidator;
use App\Validators\TransactionValidator;
use DateTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Views\Twig;

class TransactionsController
{

    public function __construct(
        private readonly ValidatorFactoryInterface     $validatorFactory,
        private readonly CategoryService               $categoryService,
        private readonly TransactionService            $transactionService,
        private readonly ResponseFormatter             $responseFormatter,
        private readonly RequestService                $requestService,
        private readonly Twig                          $twig,
        private readonly EntityManagerServiceInterface $entityManagerService
    ) {
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = $request->getAttribute('user')->getId();
        // this one is cached
        $categories = array_map(fn($category) => ['id' => $category->getId(), 'name' => $category->getName()]
            , array_values($this->categoryService->getAllKeyedByNames($userId)));

        return $this->twig->render(
            $response,
            'transactions/index.twig',
            ['categories' => $categories]
        );
    }

    public function load(Request $request, Response $response): Response
    {
        $queryParamsDTO = $this->requestService->getDataTableQueryParams($request);

        $transactions = $this->transactionService->getPaginatedData($queryParamsDTO);

        $totalTransactions = count($transactions);

        $transactions = array_map(function (Transaction $transaction) {
            return [
                'id'          => $transaction->getId(),
                'description' => $transaction->getDescription(),
                'date'        => $transaction->getDate()->format('m/d/Y g:i a'),
                'amount'      => $transaction->getAmount(),
                'category'    => $transaction->getCategory()->getName(),
                'receipts'    => $transaction->getReceipts()->map(fn(Receipt $receipt) => [
                    'id'       => $receipt->getId(),
                    'filename' => $receipt->getFilename()
                ])->toArray(),
                'reviewed'    => $transaction->isReviewed()
            ];
        }, (array)$transactions->getIterator());

        return $this->responseFormatter->asJson($response, [
            'data'            => $transactions,
            'draw'            => $queryParamsDTO->draw,
            'recordsTotal'    => $totalTransactions,
            'recordsFiltered' => $totalTransactions
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(TransactionValidator::class)->validate($request->getParsedBody());

        $transaction = $this->transactionService->create(
            new TransactionDTO(
                $data['description'],
                new DateTime($data['date']),
                (float)$data['amount'],
                $data['category']
            )
            ,
            $request->getAttribute('user')
        );

        $this->entityManagerService->sync($transaction);

        return $this->responseFormatter->asJson($response, $data);
    }

    public function delete(Response $response, Transaction $transaction): Response
    {
        $this->entityManagerService->delete($transaction, true);

        return $response;
    }

    public function get(Response $response, Transaction $transaction): Response
    {
        $data = [
            'id'          => $transaction->getId(),
            'description' => $transaction->getDescription(),
            'date'        => $transaction->getDate(),
            'amount'      => $transaction->getAmount(),
            'category'    => $transaction->getCategory()->getId(),
        ];

        return $this->responseFormatter->asJson($response, $data);
    }

    public function update(Request $request, Response $response, Transaction $transaction): Response
    {
        $data = $this->validatorFactory->make(TransactionValidator::class)->validate($request->getParsedBody());

        $transaction = $this->transactionService->update(
            $transaction,
            new TransactionDTO(
                $data['description'],
                new DateTime($data['date']),
                (float)$data['amount'],
                $data['category']
            )
        );

        $this->entityManagerService->sync($transaction);


        return $response;
    }

    public function upload(Request $request, Response $response): Response
    {
        // 1. read the file and validate
        /***@var UploadedFileInterface $file */

        $files = $this->validatorFactory->make(TransactionFileValidator::class)->validate($request->getUploadedFiles());
        $file  = $files['transaction'];

        // 3. do processing
        $user = $request->getAttribute('user');

        $this->transactionService->uploadFromFile($file->getStream()->getMetadata('uri'), $user);

        return $this->responseFormatter->asJson($response, []);
    }

    public function toggleReviewed(Response $response, Transaction $transaction): Response
    {
        $this->transactionService->toggleReviewed($transaction);

        $this->entityManagerService->sync($transaction);

        return $response;
    }


}