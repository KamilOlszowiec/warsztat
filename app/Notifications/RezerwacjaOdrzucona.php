<?php
namespace App\Notifications;

use App\Models\Rezerwacja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RezerwacjaOdrzucona extends Notification
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
        $this->rezerwacja->loadMissing(['uslugi', 'pojazd']);

        $termin = $this->rezerwacja->termin_rozpoczecia;

        return (new MailMessage)
            ->subject('Nie możemy przyjąć wizyty ' . $termin->format('d.m') . ' o ' . $termin->format('H:i'))
            ->view('emails.rezerwacja-odrzucona', [
                'rezerwacja' => $this->rezerwacja,
                'imie'       => $notifiable->imie,
                'url'        => route('klient.reservations.create', ['pojazd' => $this->rezerwacja->id_pojazdu]),
            ]);
    }
}