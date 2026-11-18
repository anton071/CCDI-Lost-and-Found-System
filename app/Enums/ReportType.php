<?php

namespace App\Enums;

enum ReportType: string
{
    case Lost = 'lost';
    case Found = 'found';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function opposite(): self
    {
        return $this === self::Lost ? self::Found : self::Lost;
    }
}
