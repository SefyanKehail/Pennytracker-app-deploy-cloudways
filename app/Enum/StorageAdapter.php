<?php

namespace App\Enum;

enum StorageAdapter: string
{
    case Local = 'local';
    case Remote_DO = 's3';

}
