<?php

namespace App\DTO;

use App\Enum\SameSite;

class SessionParamsDTO
{
    public function __construct(
        public readonly string   $name,
        public readonly bool     $secure,
        public readonly bool     $httpOnly,
        public readonly SameSite $sameSite
    ) {
    }

}