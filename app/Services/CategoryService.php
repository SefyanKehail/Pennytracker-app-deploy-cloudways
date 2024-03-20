<?php

declare(strict_types=1);

namespace App\Services;


use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\UserInterface;
use App\DTO\DataTableQueryParamsDTO;
use App\Entity\Category;
use App\Entity\Transaction;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Psr\SimpleCache\CacheInterface;

class CategoryService
{
    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly CacheInterface                $cache
    ) {
    }

    public function create(array $data, UserInterface $user): Category
    {
        $category = new Category();

        $category->setUser($user);

        return $this->update($category, $data);
    }


    public function getAll(): array
    {
        return $this->entityManagerService->getRepository(Category::class)->findAll();
    }

    public function getAllNames(): array
    {
        $query = $this->entityManagerService->getRepository(Category::class)->createQueryBuilder('c');
        return $query->select('c.id', 'c.name')->getQuery()->getArrayResult();
    }


    public function getById(?string $categoryId): ?Category
    {
        if ($categoryId === null || ! $category = $this->entityManagerService->getRepository(Category::class
            )->find($categoryId)) {
            return null;
        }

        return $category;
    }

    public function findByName(string $name): ?Category
    {
        return $this->entityManagerService->getRepository(Category::class)->findOneBy(['name' => $name]);
    }

    public function update(Category $category, array $data): Category
    {
        $this->cache->delete('categories_keyed_by_name_' . $category->getUser()->getId());

        $category->setName($data['name']);

        return $category;
    }

    public function getPaginatedData(DataTableQueryParamsDTO $queryParamsDTO): Paginator
    {
        $query = $this->entityManagerService->getRepository(Category::class)
                                            ->createQueryBuilder('cat')
                                            ->setFirstResult($queryParamsDTO->start)
                                            ->setMaxResults($queryParamsDTO->length);


        // filtering

        $query->where('cat.name LIKE :name')->setParameter(
            'name',
            '%' . addcslashes($queryParamsDTO->search, '%_') . '%'
        );

        // sorting

        $orderBy = in_array($queryParamsDTO->orderBy,
            ['name', 'createdAt', 'updatedAt']
        ) ? $queryParamsDTO->orderBy : 'updatedAt';

        $orderDir = strtolower($queryParamsDTO->orderDir) === 'asc' ? 'asc' : 'desc';

        $query->orderBy('cat.' . $orderBy, $orderDir);
        return new Paginator($query, fetchJoinCollection: false);
    }

    public function getAllKeyedByNames(int $user_id): array
    {
//        if ($this->cache->has('categories_keyed_by_name_' . $user_id)) {
//            return $this->cache->get('categories_keyed_by_name_' . $user_id);
//        }
        $categories = $this->entityManagerService->getRepository(Category::class)->findAll();

        $categoriesKeyedByNames = [];

        foreach ($categories as $category) {
            $categoriesKeyedByNames[strtolower($category->getName())] = $category;
        }
//
//        $this->cache->set('categories_keyed_by_name_' . $user_id, $categoriesKeyedByNames);

        return $categoriesKeyedByNames;
    }

    public function getTopSpendingCategories(\DateTime $startDate, \DateTime $endDate, int $limit): array
    {
        return $this->entityManagerService
            ->getRepository(Transaction::class)
            ->createQueryBuilder('tr')
            ->select('c.name as name', 'SUM(tr.amount) as total')
            ->leftJoin('tr.category', 'c')
            ->where('tr.amount < 0')
            ->andWhere('tr.date BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('name')
            ->orderBy('total', 'asc')
            ->getQuery()
            ->setMaxResults($limit)
            ->getArrayResult();
    }
}