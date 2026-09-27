<?php

namespace App\Enums;

/**
 * The 2005 system split people into two folders of pages, `authuser/admin`
 * and `authuser/members`. The split is now a value on the user, checked by
 * a policy, so both roles share the same screens.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Broker = 'broker';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
