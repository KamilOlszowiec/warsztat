@extends('errors.layout')

@section('kod', '403')
@section('tytul', 'Brak dostępu')
@section('opis')
    @php $komunikat = isset($exception) ? $exception->getMessage() : ''; @endphp
    {{ $komunikat && $komunikat !== 'Forbidden' && $komunikat !== 'This action is unauthorized.'
        ? $komunikat
        : 'Nie masz uprawnień do tej strony. Jeśli uważasz, że to pomyłka, skontaktuj się z warsztatem.' }}
@endsection
