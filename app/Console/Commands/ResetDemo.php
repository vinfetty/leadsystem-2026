<?php

namespace App\Console\Commands;

use App\Support\Demo;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('demo:reset')]
#[Description('Rebuild the database with fresh invented data. Runs only in demo mode.')]
class ResetDemo extends Command
{
    /**
     * Everything is dropped, so this refuses outright unless demo mode is on.
     */
    public function handle(): int
    {
        if (! Demo::enabled()) {
            $this->error('Demo mode is off, so nothing was reset.');

            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        return self::SUCCESS;
    }
}
