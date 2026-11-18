<?php

declare(strict_types=1);

class ValidationException extends RuntimeException
{
}

function postNumber(string $key, string $label, bool $allowZero = false): float
{
    $value = postString($key);
    if ($value === '' || !is_numeric($value)) {
        throw new ValidationException($label . ' must be a number.');
    }

    $number = (float) $value;
    if ($number < 0 || (!$allowZero && $number <= 0)) {
        throw new ValidationException($label . ($allowZero ? ' cannot be negative.' : ' must be greater than zero.'));
    }
    return $number;
}
