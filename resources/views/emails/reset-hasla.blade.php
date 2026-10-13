@extends('emails.layout')
@section('tytul', 'Resetowanie hasła')

@section('tresc')
    <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #18181B;">Cześć, {{ $imie }}!</h1>

    <p style="margin: 0 0 14px; font-size: 15px; line-height: 1.6; color: #3F3F46;">
        Otrzymaliśmy prośbę o zresetowanie hasła do Twojego konta. Kliknij przycisk poniżej, aby ustawić nowe hasło.
    </p>

    @include('emails.partials.przycisk', ['url' => $url, 'tekst' => 'Ustaw nowe hasło'])

    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #52525B;">
        Link wygaśnie za {{ $minuty }} minut. Jeśli to nie Ty prosiłeś o zmianę, nic nie musisz robić – Twoje obecne hasło pozostaje bez zmian.
    </p>

    @include('emails.partials.link-zapasowy', ['url' => $url])
@endsection