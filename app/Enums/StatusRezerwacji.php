<?php

namespace App\Enums;

enum StatusRezerwacji: string
{
    case Oczekujaca   = 'oczekująca';
    case Zatwierdzona = 'zatwierdzona';
    case Odrzucona    = 'odrzucona';
    case Anulowana    = 'anulowana';
    case Zakonczona   = 'zakończona';

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
