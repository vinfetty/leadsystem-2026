<?php

use App\Http\Controllers\SignOutController;
use App\Livewire\BuyerDirectory;
use App\Livewire\LeadDetail;
use App\Livewire\LeadInbox;
use App\Livewire\SignIn;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/leads');

Route::middleware('guest')->group(function (): void {
    Route::livewire('/login', SignIn::class)->name('login');
});

Route::middleware('auth')->group(function (): void {
    Route::livewire('/leads', LeadInbox::class)->name('leads.index');
    Route::livewire('/leads/{lead}', LeadDetail::class)->whereNumber('lead')->name('leads.show');
    Route::livewire('/buyers', BuyerDirectory::class)->name('buyers.index');
    Route::post('/logout', SignOutController::class)->name('logout');
});
