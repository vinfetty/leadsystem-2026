<?php

namespace App\Enums;

enum CreditRating: string
{
    case Excellent = 'excellent';
    case Good = 'good';
    case Fair = 'fair';
    case Poor = 'poor';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
