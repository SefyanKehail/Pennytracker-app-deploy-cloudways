<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class SendPasswordResetEmailValidator implements ValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['email']);
        $v->rule('email', 'email');

        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}