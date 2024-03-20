<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class CategoryValidator implements ValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['name']);
        $v->rule('lengthMax', 'name', 50);

        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}