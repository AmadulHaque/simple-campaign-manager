<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Dashboard
    Route::get('/dashboard', function () {
        return inertia('Dashboard');
    })->name('dashboard');

    // Contacts
    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/bulk-delete', [ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/contacts', [CampaignController::class, 'updateContacts'])->name('campaigns.contacts');

    // Campaign stats
    Route::get('/campaigns/{campaign}/stats', [CampaignController::class, 'stats'])->name('campaigns.stats');
});

require __DIR__.'/settings.php';
