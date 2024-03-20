<?php

namespace App\Validators;

use App\Contracts\ValidatorInterface;
use App\Exceptions\ValidationException;
use Valitron\Validator;

class ChartCustomDateValidator implements ValidatorInterface
{

    public function validate(array $data): array
    {
        $v = new Validator($data);
        $v->rule('required', 'granularity');
        $v->rule('required', 'startDate')->label('Start date');
        $v->rule('required', 'endDate')->label('End date');
        $v->rule('date', 'startDate');
        $v->rule('date', 'endDate');

        if (! $v->validate()) {
            throw new ValidationException($v->errors());
        }

        return $data;
    }
}