@extends('emails.layout')
@section('tytul', 'Wizyta potwierdzona')

@section('tresc')
    @php
        $r = $rezerwacja;
        $etykieta = 'font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; color: #71717A;';
    @endphp

    <span style="display: inline-block; margin-bottom: 14px; padding: 3px 9px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; font-weight: 700; background: #DCFCE7; color: #166534; border-radius: 2px;">ZATWIERDZONA</span>

    <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: 700; color: #18181B;">{{ $imie }}, Twoja wizyta jest potwierdzona</h1>

    <p style="margin: 0 0 22px; font-size: 15px; line-height: 1.6; color: #3F3F46;">
        Warsztat zatwierdził Twoją rezerwację. Poniżej wszystkie szczegóły – do zobaczenia!
    </p>

    {{-- Podsumowanie --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background: #FAFAFA; border: 1px solid #E4E4E7; border-radius: 2px;">
        <tr>
            <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">TERMIN</div>
                <div style="font-size: 16px; font-weight: 700; color: #18181B;">{{ $r->termin_rozpoczecia->translatedFormat('l, j F Y') }}</div>
                <div style="font-size: 15px; color: #27272A; margin-top: 2px;">
                    {{ $r->termin_rozpoczecia->format('H:i') }} – {{ $r->termin_zakonczenia->format('H:i') }}
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 6px;">USŁUGI</div>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    @foreach ($r->uslugi as $u)
                        <tr>
                            <td style="padding: 2px 0; font-size: 14px; color: #27272A;">{{ $u->nazwa }}</td>
                            <td align="right" style="padding: 2px 0; font-size: 14px; color: #27272A; white-space: nowrap;">
                                {{ number_format($u->pivot->cena, 2, ',', ' ') }} zł
                            </td>
                        </tr>
                    @endforeach
                </table>
            </td>
        </tr>
        <tr>
            <td width="50%" valign="top" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">POJAZD</div>
                <div style="font-size: 14px; color: #27272A;">
                    {{ $r->pojazd->marka }} {{ $r->pojazd->model }} ({{ $r->pojazd->numer_rejestracyjny }})
                </div>
            </td>
            <td width="50%" valign="top" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">MECHANIK</div>
                <div style="font-size: 14px; color: #27272A;">
                    {{ $r->mechanik ? $r->mechanik->imie . ' ' . $r->mechanik->nazwisko : '—' }}
                </div>
            </td>
        </tr>
        <tr>
            <td style="padding: 14px 18px; font-size: 14px; color: #52525B;">Szacowany koszt</td>
            <td align="right" style="padding: 14px 18px; font-size: 18px; font-weight: 700; color: #18181B; white-space: nowrap;">
                {{ number_format($r->laczna_cena, 2, ',', ' ') }} zł
            </td>
        </tr>
    </table>

    @include('emails.partials.przycisk', ['url' => $url, 'tekst' => 'Zobacz szczegóły rezerwacji'])

    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #52525B;">
        Nie możesz przyjechać? Anuluj wizytę w panelu klienta, żeby zwolnić termin dla innych.
    </p>

    @include('emails.partials.link-zapasowy', ['url' => $url])
@endsection