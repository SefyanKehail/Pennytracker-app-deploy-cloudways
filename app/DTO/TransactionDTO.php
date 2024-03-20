<?php

namespace App\DTO;

use App\Entity\Category;
use ArrayIterator;
use DateTime;
use Traversable;

class TransactionDTO implements \IteratorAggregate
{
    public function __construct(
        public string $description,
        public DateTime $date,
        public float $amount,
        public Category $category
    ) {
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this);
    }
}