<?php

namespace App\DTO;

class QueryParamsDTO
{
    public function __construct(
        public int    $draw,
        public int    $start,
        public int    $length,
        public string $orderBy,
        public string $orderDir,
        public string $search

    ) {
    }
}