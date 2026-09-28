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

    public function meets(self $minimum): bool
    {
        return $this->rank() >= $minimum->rank();
    }

    private function rank(): int
    {
        return match ($this) {
            self::Poor => 1,
            self::Fair => 2,
            self::Good => 3,
            self::Excellent => 4,
        };
    }
}
