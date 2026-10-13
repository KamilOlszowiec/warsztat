<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mail z linkiem weryfikacyjnym po rejestracji i po zmianie adresu e-mail
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Potwierdź swój adres e-mail')
                ->view('emails.weryfikacja', [
                    'imie'   => $notifiable->imie,
                    'url'    => $url,
                    'minuty' => config('auth.verification.expire', 60),
                ]);
        });

        // Mail z linkiem do ustawienia nowego hasła
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            $broker = config('auth.defaults.passwords');

            return (new MailMessage)
                ->subject('Resetowanie hasła')
                ->view('emails.reset-hasla', [
                    'imie'   => $notifiable->imie,
                    'url'    => $url,
                    'minuty' => config("auth.passwords.{$broker}.expire", 60),
                ]);
        });
    }
}