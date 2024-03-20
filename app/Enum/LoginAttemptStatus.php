<?php

namespace App\Enum;

enum LoginAttemptStatus
{
    case SUCCESS;
    case FAILED;
    case TWO_FACTOR_AUTH;
}
