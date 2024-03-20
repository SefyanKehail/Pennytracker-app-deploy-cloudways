<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class Toggle2FAValidator implements ValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['password']);

        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}