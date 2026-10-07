<?php

use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [RegistrationController::class, 'welcome'])->name('register.welcome');

Route::prefix('register')->group(function () {
    Route::post('/welcome', [RegistrationController::class, 'continueWelcome'])->name('register.welcome.continue');

    Route::get('/experience', [RegistrationController::class, 'experience'])->name('register.experience');
    Route::post('/experience', [RegistrationController::class, 'storeExperience'])->name('register.experience.store');

    Route::get('/level', [RegistrationController::class, 'level'])->name('register.level');
    Route::post('/level', [RegistrationController::class, 'storeLevel'])->name('register.level.store');

    Route::get('/personal', [RegistrationController::class, 'personal'])->name('register.personal');
    Route::post('/personal', [RegistrationController::class, 'storePersonal'])->name('register.personal.store');

    Route::get('/payment', [RegistrationController::class, 'payment'])->name('register.payment');
    Route::post('/payment', [RegistrationController::class, 'submit'])
        ->middleware('throttle:8,1')
        ->name('register.submit');

    Route::get('/confirmation', [RegistrationController::class, 'confirmation'])->name('register.confirmation');

    Route::get('/preview/photo', [RegistrationController::class, 'previewPhoto'])->name('register.preview.photo');
    Route::get('/preview/proof', [RegistrationController::class, 'previewProof'])->name('register.preview.proof');
});
