<?php

namespace App\Validators;

use App\Contracts\AuthInterface;
use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class PasswordResetValidator implements ValidatorInterface
{
    public function __construct(private readonly AuthInterface $auth)
    {
    }

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['password']);
        $v->rule('required', ['confirmPassword'])->label('Confirm Password');
        $v->rule(
            fn($field, $value, $params, $fields) => $this->auth->isDifferent($value, $data['user']),
            "password"
        )->message("New password has to be different");
        $v->rule('equals',
            'confirmPassword',
            'password'
        )->message('New password and confirmation are not matching');
        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}