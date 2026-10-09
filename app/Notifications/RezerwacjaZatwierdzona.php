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
        $url = URL::temporarySignedRoute(
            'reservations.public-show',
            now()->addDays(30),
            ['reservation' => $this->rezerwacja->id_rezerwacji]
        );

        return (new MailMessage)
            ->subject('Twoja rezerwacja została zatwierdzona')
            ->greeting('Dzień dobry, ' . $notifiable->imie . '!')
            ->line('Twoja rezerwacja została zatwierdzona przez warsztat.')
            ->line('Usługa: ' . $this->rezerwacja->usluga->nazwa)
            ->line('Pojazd: ' . $this->rezerwacja->pojazd->marka . ' ' . $this->rezerwacja->pojazd->model)
            ->line('Termin: ' . $this->rezerwacja->termin_rozpoczecia->format('d.m.Y H:i'))
            ->action('Zobacz szczegóły rezerwacji', $url)
            ->line('Do zobaczenia w warsztacie!');
    }
}