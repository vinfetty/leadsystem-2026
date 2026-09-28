<?php

namespace App\Support;

/**
 * Whether this installation is the public demo, and who may sign in to it with one click.
 */
final class Demo
{
    /** @var array<string, string> */
    public const ACCOUNTS = [
        'admin' => 'admin@example.com',
        'processor' => 'processor@example.com',
    ];

    public static function enabled(): bool
    {
        return (bool) config('leads.demo');
    }
}
