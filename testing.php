<?php

use App\DTO\TransactionDTO;
use App\Entity\User;
use App\Services\CategoryService;
use Doctrine\ORM\EntityManager;
use Slim\App;
use Ramsey\Uuid\Uuid;

require 'bootstrap.php';
/**
 * @var App $app
 * @var EntityManager $entityManager
 */



$a = ["a",'b'];
$b = ["c",'d'];

var_dump([...$a, ...$b]);
