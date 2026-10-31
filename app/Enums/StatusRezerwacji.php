<?php

namespace App\Enums;

/**
 * Statusy rezerwacji wraz z ich opisem i kolorami w interfejsie.
 * Wartości odpowiadają typowi ENUM kolumny rezerwacje.status.
 */
enum StatusRezerwacji: string
{
    case Oczekujaca   = 'oczekująca';
    case Zatwierdzona = 'zatwierdzona';
    case Odrzucona    = 'odrzucona';
    case Anulowana    = 'anulowana';
    case Zakonczona   = 'zakończona';

    /**
     * Statusy wizyt, które zajmują termin w grafiku mechanika.
     */
    public static function aktywne(): array
    {
        return [self::Oczekujaca->value, self::Zatwierdzona->value];
    }

    public function etykieta(): string
    {
        return match ($this) {
            self::Oczekujaca   => 'Oczekująca',
            self::Zatwierdzona => 'Zatwierdzona',
            self::Odrzucona    => 'Odrzucona',
            self::Anulowana    => 'Anulowana',
            self::Zakonczona   => 'Zakończona',
        };
    }

    // Tło i kolor tekstu etykiety statusu
    public function klasy(): string
    {
        return match ($this) {
            self::Oczekujaca   => 'bg-yellow-900/40 text-yellow-400',
            self::Zatwierdzona => 'bg-green-900/40 text-green-400',
            self::Odrzucona    => 'bg-red-900/40 text-red-400',
            self::Anulowana    => 'bg-zinc-800 text-zinc-400',
            self::Zakonczona   => 'bg-blue-900/40 text-blue-400',
        };
    }

    // Kolor kropki na osi czasu w historii pojazdu
    public function kropka(): string
    {
        return match ($this) {
            self::Oczekujaca   => 'bg-yellow-400',
            self::Zatwierdzona => 'bg-green-400',
            self::Odrzucona    => 'bg-red-400',
            self::Anulowana    => 'bg-zinc-500',
            self::Zakonczona   => 'bg-blue-400',
        };
    }
}
