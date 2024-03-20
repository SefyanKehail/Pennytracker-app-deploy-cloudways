<?php

namespace App\Contracts;

interface ValidatorFactoryInterface
{
    public function make(string $class): ValidatorInterface;
}