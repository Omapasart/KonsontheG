<?php

use App\Http\Controllers\Admin\ApplicantController;
use App\Http\Controllers\Admin\ApplicantFileController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CategoryTransferController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\WaitingListController;
use App\Http\Controllers\CategoryTransferResponseController;
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
    Route::get('/payment/qr', [RegistrationController::class, 'downloadPaymentQr'])->name('register.payment.qr');
    Route::post('/payment', [RegistrationController::class, 'submit'])
        ->middleware('throttle:8,1')
        ->name('register.submit');

    Route::get('/confirmation', [RegistrationController::class, 'confirmation'])->name('register.confirmation');
    Route::post('/start-again', [RegistrationController::class, 'startAgain'])->name('register.start-again');
    Route::get('/email-taken', [RegistrationController::class, 'emailTaken'])->name('register.email-taken');
    Route::get('/category-full', [RegistrationController::class, 'categoryFull'])->name('register.category-full');

    Route::get('/preview/photo', [RegistrationController::class, 'previewPhoto'])->name('register.preview.photo');
    Route::get('/preview/proof', [RegistrationController::class, 'previewProof'])->name('register.preview.proof');
});

Route::prefix('category-transfer')->middleware('throttle:20,1')->group(function () {
    Route::get('/{transfer}', [CategoryTransferResponseController::class, 'show'])
        ->middleware('signed')
        ->name('transfers.show');
    Route::post('/{transfer}/accept', [CategoryTransferResponseController::class, 'accept'])
        ->name('transfers.accept');
    Route::post('/{transfer}/decline', [CategoryTransferResponseController::class, 'decline'])
        ->name('transfers.decline');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store'])
            ->middleware('throttle:8,1')
            ->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/profile', ProfileController::class)->name('profile');

        Route::get('/waiting-list', WaitingListController::class)->name('waiting-list');
        Route::get('/applicants', [ApplicantController::class, 'index'])->name('applicants.index');
        Route::get('/applicants/{registration}', [ApplicantController::class, 'show'])->name('applicants.show');
        Route::post('/applicants/{registration}/approve', [ApplicantController::class, 'approve'])->name('applicants.approve');
        Route::post('/applicants/{registration}/reject', [ApplicantController::class, 'reject'])->name('applicants.reject');
        Route::post('/applicants/{registration}/verify-payment', [ApplicantController::class, 'verifyPayment'])->name('applicants.verify-payment');
        Route::post('/applicants/{registration}/reject-payment', [ApplicantController::class, 'rejectPayment'])->name('applicants.reject-payment');
        Route::post('/applicants/{registration}/withdraw', [ApplicantController::class, 'withdraw'])->name('applicants.withdraw');
        Route::post('/applicants/{registration}/promote', [ApplicantController::class, 'promote'])->name('applicants.promote');
        Route::post('/applicants/{registration}/category-transfer', [CategoryTransferController::class, 'store'])->name('applicants.category-transfer');

        Route::get('/applicants/{registration}/photo', [ApplicantFileController::class, 'photo'])->name('applicants.photo');
        Route::get('/applicants/{registration}/photo/download', [ApplicantFileController::class, 'downloadPhoto'])->name('applicants.photo.download');
        Route::get('/applicants/{registration}/proof', [ApplicantFileController::class, 'proof'])->name('applicants.proof');
        Route::get('/applicants/{registration}/proof/download', [ApplicantFileController::class, 'downloadProof'])->name('applicants.proof.download');
    });
});
