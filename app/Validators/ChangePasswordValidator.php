<?php

namespace App\Validators;

use App\Contracts\AuthInterface;
use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\ValidatorInterface;
use App\Entity\User;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class ChangePasswordValidator implements ValidatorInterface
{

    public function __construct(private readonly AuthInterface $auth)
    {
    }

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['password']);
        $v->rule('required', ['newPassword'])->label('New Password');
        $v->rule('required', ['confirmPassword'])->label('Confirm Password');
        $v->rule(
            fn($field, $value, $params, $fields) => $this->auth->isDifferent($value, $data['user']),
            "newPassword"
        )->message("New password has to be different");
        $v->rule('equals',
            'confirmPassword',
            'newPassword'
        )->message('New password and confirmation are not matching');
        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}