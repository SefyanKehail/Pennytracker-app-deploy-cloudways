<?php

namespace App\Validators;

use App\Contracts\EntityManagerServiceInterface;
use App\Contracts\ValidatorInterface;
use App\Entity\User;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class UserRegistrationValidator implements ValidatorInterface
{
    public function __construct(private readonly EntityManagerServiceInterface $entityManagerService)
    {
    }

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', ['name', 'email', 'password']);
        $v->rule('email', 'email');
        $v->rule('required', ['confirmPassword'])->label('Confirm Password');
        $v->rule(
            fn($field, $value, $params, $fields) => !$this->entityManagerService->getRepository(User::class
            )->count([$field => $value]),
            "email"
        )->message("{field} is already taken");
        $v->rule('equals',
            'confirmPassword',
            'password'
        )->message('Confirmation password and password have to be equal');
        if (!$v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}