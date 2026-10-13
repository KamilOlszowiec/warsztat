<?php
namespace App\Notifications;

use App\Models\Rezerwacja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class RezerwacjaZatwierdzona extends Notification
{
    use Queueable;

    public function __construct(public Rezerwacja $rezerwacja)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $this->rezerwacja->loadMissing(['uslugi', 'pojazd', 'mechanik']);

        $url = URL::temporarySignedRoute(
            'reservations.public-show',
            now()->addDays(30),
            ['reservation' => $this->rezerwacja->id_rezerwacji]
        );

        $termin = $this->rezerwacja->termin_rozpoczecia;

        return (new MailMessage)
            ->subject('Wizyta potwierdzona: ' . $termin->translatedFormat('l, d.m') . ' o ' . $termin->format('H:i'))
            ->view('emails.rezerwacja-zatwierdzona', [
                'rezerwacja' => $this->rezerwacja,
                'imie'       => $notifiable->imie,
                'url'        => $url,
            ]);
    }
}