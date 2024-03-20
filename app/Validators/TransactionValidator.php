<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use App\Services\CategoryService;
use Valitron\Validator;


class TransactionValidator implements ValidatorInterface
{
    public function __construct(private readonly CategoryService $categoryService)
    {
    }

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['description', 'date', 'amount', 'category']);
        $v->rule('numeric', 'amount');
        $v->rule('date', 'date');
        $v->rule(
            function ($field, $value, $params, $fields) use (&$data) {

                if (! $value) {
                    return false;
                }

                $category = $this->categoryService->getById($value);

                if ($category) {
                    $data[$field] = $category;
                    return true;
                }

                return false;
            },
            'category'
        )->message("{field} is not a valid category");

        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}