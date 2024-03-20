<?php

namespace App\Contracts;

interface ValidatorInterface
{
    public function validate(array $data): array;
}