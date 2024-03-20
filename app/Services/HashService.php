<?php

namespace App\Services;

class HashService
{
    public function passwordHash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}