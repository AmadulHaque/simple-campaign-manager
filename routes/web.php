<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Contacts
    Route::resource('contacts', ContactController::class);
    Route::post('/contacts/bulk-delete', [ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
    Route::post('/contacts/import', [ContactController::class, 'import'])->name('contacts.import');

    // Campaigns
    Route::resource('campaigns', CampaignController::class);
    Route::post('/campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('/campaigns/{campaign}/updateContacts', [CampaignController::class, 'updateContacts'])->name('campaigns.updateContacts');
});

require __DIR__.'/settings.php';
