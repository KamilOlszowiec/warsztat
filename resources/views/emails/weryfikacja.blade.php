@extends('emails.layout')
@section('tytul', 'Potwierdź swój adres e-mail')

@section('tresc')
    <h1 style="margin: 0 0 16px; font-size: 22px; font-weight: 700; color: #18181B;">Cześć, {{ $imie }}!</h1>

    <p style="margin: 0 0 14px; font-size: 15px; line-height: 1.6; color: #3F3F46;">
        Dziękujemy za założenie konta. Kliknij przycisk poniżej, aby potwierdzić swój adres e-mail – potem od razu umówisz pierwszą wizytę.
    </p>

    @include('emails.partials.przycisk', ['url' => $url, 'tekst' => 'Potwierdź adres e-mail'])

    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #52525B;">
        Link jest ważny przez {{ $minuty }} minut. Jeśli to nie Ty zakładałeś konto, po prostu zignoruj tę wiadomość.
    </p>

    @include('emails.partials.link-zapasowy', ['url' => $url])
@endsection