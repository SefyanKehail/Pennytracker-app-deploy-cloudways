<?php

namespace App\DTO;

class UserProfileDTO
{

    public function __construct(
        public string $email,
        public string $name,
    )
    {
    }
}