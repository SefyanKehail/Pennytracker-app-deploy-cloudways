<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\ValidatorFactoryInterface;
use App\Entity\Category;
use App\ResponseFormatter;
use App\Services\CategoryService;
use App\Services\RequestService;
use App\Validators\CategoryValidator;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\SimpleCache\CacheInterface;
use Slim\Views\Twig;

class CategoriesController
{

    public function __construct(
        private readonly ValidatorFactoryInterface     $validatorFactory,
        private readonly CategoryService               $categoryService,
        private readonly ResponseFormatter             $responseFormatter,
        private readonly RequestService                $requestService,
        private readonly Twig                          $twig,
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly CacheInterface                $cache
    ) {
    }

    public function index(Response $response): Response
    {
        return $this->twig->render($response, 'categories/index.twig');
    }

    public function load(Request $request, Response $response): Response
    {
        $queryParamsDTO = $this->requestService->getDataTableQueryParams($request);

        $categories = $this->categoryService->getPaginatedData($queryParamsDTO);

        $totalCategories = count($categories);

        $categories = array_map(function (Category $category) {
            return [
                'id'        => $category->getId(),
                'name'      => $category->getName(),
                'createdAt' => $category->getCreatedAt()->format('m/d/Y g:i a'),
                'updatedAt' => $category->getUpdatedAt()->format('m/d/Y g:i a'),
            ];
        }, (array)$categories->getIterator());


        return $this->responseFormatter->asJson($response, [
            'data'            => $categories,
            'draw'            => $queryParamsDTO->draw,
            'recordsTotal'    => $totalCategories,
            'recordsFiltered' => $totalCategories
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $this->validatorFactory->make(CategoryValidator::class)->validate($request->getParsedBody());

        $category = $this->categoryService->create($data, $request->getAttribute('user'));

        $this->entityManagerService->sync($category);

        return $this->responseFormatter->asJson($response, $data);
    }

    public function delete(Response $response, Category $category): Response
    {
        $this->entityManagerService->delete($category, true);

        $this->cache->delete('categories_keyed_by_name_' . $category->getUser()->getId());

        return $response;
    }

    public function get(Response $response, Category $category): Response
    {
        $data = ['id' => $category->getId(), 'name' => $category->getName()];

        return $this->responseFormatter->asJson($response, $data);
    }

    public function update(Request $request, Response $response, Category $category): Response
    {
        $data = $this->validatorFactory->make(CategoryValidator::class)->validate($request->getParsedBody());

        $category = $this->categoryService->update($category, $data);

        $this->entityManagerService->sync($category);

        return $response;
    }
}