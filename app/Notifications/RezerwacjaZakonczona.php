<?php
namespace App\Notifications;

use App\Models\Rezerwacja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RezerwacjaZakonczona extends Notification
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

        $pojazd = $this->rezerwacja->pojazd;

        return (new MailMessage)
            ->subject('Dziękujemy za wizytę – ' . $pojazd->marka . ' ' . $pojazd->model)
            ->view('emails.rezerwacja-zakonczona', [
                'rezerwacja'   => $this->rezerwacja,
                'imie'         => $notifiable->imie,
                'urlKolejna'   => route('klient.reservations.create', ['pojazd' => $pojazd->id_pojazdu]),
                'urlHistoria'  => route('klient.vehicles.index'),
            ]);
    }
}