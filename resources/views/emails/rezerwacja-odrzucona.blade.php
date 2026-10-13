@extends('emails.layout')
@section('tytul', 'Nie możemy przyjąć Twojej wizyty')

@section('tresc')
    @php
        $r = $rezerwacja;
        $etykieta = 'font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; color: #71717A;';
    @endphp

    <span style="display: inline-block; margin-bottom: 14px; padding: 3px 9px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; font-weight: 700; background: #FEE2E2; color: #991B1B; border-radius: 2px;">ODRZUCONA</span>

    <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: 700; color: #18181B;">{{ $imie }}, nie możemy przyjąć Twojej wizyty</h1>

    <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.6; color: #3F3F46;">
        Przepraszamy – warsztat nie może zrealizować tej rezerwacji. Termin został zwolniony, a Ty możesz od razu wybrać inny.
    </p>

    {{-- Powód odrzucenia (tylko gdy pracownik go podał) --}}
    @if ($r->powod_odrzucenia)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
               style="margin-bottom: 20px; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 2px;">
            <tr>
                <td style="padding: 14px 18px;">
                    <div style="font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; color: #991B1B; margin-bottom: 4px;">POWÓD</div>
                    <div style="font-size: 14px; line-height: 1.6; color: #27272A;">{{ $r->powod_odrzucenia }}</div>
                </td>
            </tr>
        </table>
    @endif

    {{-- Której wizyty dotyczy --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background: #FAFAFA; border: 1px solid #E4E4E7; border-radius: 2px;">
        <tr>
            <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">ODRZUCONY TERMIN</div>
                <div style="font-size: 14px; color: #71717A; text-decoration: line-through;">
                    {{ $r->termin_rozpoczecia->translatedFormat('l, j F Y') }},
                    {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }}
                </div>
            </td>
        </tr>
        <tr>
            <td width="50%" valign="top" style="padding: 14px 18px;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">USŁUGI</div>
                <div style="font-size: 14px; color: #27272A;">{{ $r->nazwy_uslug }}</div>
            </td>
            <td width="50%" valign="top" style="padding: 14px 18px;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">POJAZD</div>
                <div style="font-size: 14px; color: #27272A;">
                    {{ $r->pojazd->marka }} {{ $r->pojazd->model }} ({{ $r->pojazd->numer_rejestracyjny }})
                </div>
            </td>
        </tr>
    </table>

    @include('emails.partials.przycisk', ['url' => $url, 'tekst' => 'Wybierz inny termin'])

    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #52525B;">
        Kreator otworzy się z tym samym pojazdem – wystarczy wybrać usługi i nowy termin.
    </p>
@endsection