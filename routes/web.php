<?php

use App\Http\Controllers\AuthController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\UslugaController;
use App\Http\Controllers\Admin\GodzinyPracyController;
use App\Http\Controllers\Klient\PojazdController;
use App\Http\Controllers\Klient\RezerwacjaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProfileController;

use App\Http\Controllers\Klient\RezerwacjaController as KlientRezerwacjaController;
use App\Http\Controllers\Pracownik\RezerwacjaController as PracownikRezerwacjaController;
use App\Http\Controllers\Pracownik\KlientController as PracownikKlientController;
use App\Http\Controllers\Admin\RezerwacjaController as AdminRezerwacjaController;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

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

Route::middleware(['auth', 'verified', 'role:administrator'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn() => view('admin.dashboard'))->name('dashboard');
    Route::resource('users', AdminUserController::class)->except(['show']);
    Route::resource('services', UslugaController::class)->except(['show'])->parameters(['services' => 'service']);
    Route::get('/schedule', [GodzinyPracyController::class, 'index'])->name('schedule.index');
    Route::put('/schedule', [GodzinyPracyController::class, 'update'])->name('schedule.update');

    Route::get('/reservations', [AdminRezerwacjaController::class, 'index'])->name('reservations.index');
    Route::delete('/reservations/{reservation}', [AdminRezerwacjaController::class, 'destroy'])->name('reservations.destroy');
});

Route::middleware(['auth', 'verified', 'role:pracownik,administrator'])->prefix('pracownik')->name('pracownik.')->group(function () {

    Route::get('/', function () {
        $dzisiaj = \App\Models\Rezerwacja::whereDate('termin_rozpoczecia', today())
            ->whereNotIn('status', ['odrzucona', 'anulowana'])->count();
        $oczekujace = \App\Models\Rezerwacja::where('status', 'oczekująca')->count();
        return view('pracownik.dashboard', compact('dzisiaj', 'oczekujace'));
    })->name('dashboard');

    Route::get('/reservations', [PracownikRezerwacjaController::class, 'index'])->name('reservations.index');
    Route::post('/reservations/{reservation}/approve', [PracownikRezerwacjaController::class, 'approve'])->name('reservations.approve');
    Route::post('/reservations/{reservation}/reject', [PracownikRezerwacjaController::class, 'reject'])->name('reservations.reject');
    Route::post('/reservations/{reservation}/complete', [PracownikRezerwacjaController::class, 'complete'])->name('reservations.complete');
    Route::get('/reservations/{reservation}/edit-termin', [PracownikRezerwacjaController::class, 'editTermin'])->name('reservations.edit-termin');
    Route::put('/reservations/{reservation}/termin', [PracownikRezerwacjaController::class, 'updateTermin'])->name('reservations.update-termin');

    Route::get('/clients', [PracownikKlientController::class, 'index'])->name('clients.index');
});

Route::middleware(['auth', 'verified', 'role:klient'])->prefix('klient')->name('klient.')->group(function () {
    Route::get('/', fn() => view('klient.dashboard'))->name('dashboard');
    Route::resource('vehicles', PojazdController::class)->parameters(['vehicles' => 'vehicle']);

    Route::get('/reservations', [RezerwacjaController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [RezerwacjaController::class, 'create'])->name('reservations.create');
    Route::post('/reservations', [RezerwacjaController::class, 'store'])->name('reservations.store');
    Route::post('/reservations/{reservation}/cancel', [RezerwacjaController::class, 'cancel'])->name('reservations.cancel');
});