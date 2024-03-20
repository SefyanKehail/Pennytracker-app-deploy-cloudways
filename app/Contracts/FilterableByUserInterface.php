<?php

namespace App\Contracts;

use App\Entity\User;

interface FilterableByUserInterface
{
    public function getUser(): User;
}