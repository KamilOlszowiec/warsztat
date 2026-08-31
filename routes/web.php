<?php

use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        return match (auth()->user()->rola) {
            'administrator' => redirect('/admin'),
            'pracownik' => redirect('/pracownik'),
            default => redirect('/klient'),
        };
    })->name('dashboard');

    Route::get('/email/verify', function () {
        return view('auth.verify-email');
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('status', 'Link weryfikacyjny wysłany ponownie.');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified', 'role:administrator'])->prefix('admin')->group(function () {
    Route::get('/', fn() => view('admin.dashboard'));
});

Route::middleware(['auth', 'verified', 'role:pracownik,administrator'])->prefix('pracownik')->group(function () {
    Route::get('/', fn() => view('pracownik.dashboard'));
});

Route::middleware(['auth', 'verified', 'role:klient'])->prefix('klient')->group(function () {
    Route::get('/', fn() => view('klient.dashboard'));
});