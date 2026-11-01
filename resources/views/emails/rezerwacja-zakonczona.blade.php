@extends('emails.layout')
@section('tytul', 'Dziękujemy za wizytę')

@section('tresc')
    @php
        $r = $rezerwacja;
        $etykieta = 'font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; color: #71717A;';
    @endphp

    <span style="display: inline-block; margin-bottom: 14px; padding: 3px 9px; font-family: ui-monospace, Menlo, Consolas, monospace; font-size: 11px; font-weight: 700; background: #DBEAFE; color: #1E40AF; border-radius: 2px;">ZAKOŃCZONA</span>

    <h1 style="margin: 0 0 12px; font-size: 22px; font-weight: 700; color: #18181B;">Dziękujemy za wizytę, {{ $imie }}!</h1>

    <p style="margin: 0 0 22px; font-size: 15px; line-height: 1.6; color: #3F3F46;">
        Prace przy Twoim samochodzie zostały zakończone. Poniżej podsumowanie wizyty – zapisaliśmy je też w historii pojazdu w Twoim panelu.
    </p>

    {{-- Podsumowanie --}}
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="background: #FAFAFA; border: 1px solid #E4E4E7; border-radius: 2px;">
        <tr>
            <td width="50%" valign="top" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">DATA WIZYTY</div>
                <div style="font-size: 14px; color: #27272A;">{{ $r->termin_rozpoczecia->translatedFormat('j F Y') }}</div>
            </td>
            <td width="50%" valign="top" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">POJAZD</div>
                <div style="font-size: 14px; color: #27272A;">
                    {{ $r->pojazd->marka }} {{ $r->pojazd->model }} ({{ $r->pojazd->numer_rejestracyjny }})
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 6px;">WYKONANE USŁUGI</div>
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
            <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                <div style="{{ $etykieta }} margin-bottom: 3px;">MECHANIK</div>
                <div style="font-size: 14px; color: #27272A;">
                    {{ $r->mechanik ? $r->mechanik->imie . ' ' . $r->mechanik->nazwisko : '—' }}
                </div>
            </td>
        </tr>
        @if ($r->maProtokol())
            @if ($r->protokol->przebieg !== null)
            <tr>
                <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                    <div style="{{ $etykieta }} margin-bottom: 3px;">PRZEBIEG</div>
                    <div style="font-size: 14px; color: #27272A;">{{ number_format($r->protokol->przebieg, 0, ',', ' ') }} km</div>
                </td>
            </tr>
            @endif
            <tr>
                <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                    <div style="{{ $etykieta }} margin-bottom: 3px;">WYKONANE PRACE</div>
                    <div style="font-size: 14px; line-height: 1.5; color: #27272A;">{!! nl2br(e($r->protokol->opis_prac)) !!}</div>
                </td>
            </tr>
            @if ($r->protokol->zalecenia)
                <tr>
                    <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7; background: #FFFBEB;">
                        <div style="{{ $etykieta }} margin-bottom: 3px; color: #B45309;">ZALECENIA MECHANIKA</div>
                        <div style="font-size: 14px; line-height: 1.5; color: #27272A;">{!! nl2br(e($r->protokol->zalecenia)) !!}</div>
                    </td>
                </tr>
            @endif
            @if ($r->protokol->data_nastepnej_wizyty)
                <tr>
                    <td colspan="2" style="padding: 14px 18px; border-bottom: 1px solid #E4E4E7;">
                        <div style="{{ $etykieta }} margin-bottom: 3px;">ZALECANA KOLEJNA WIZYTA</div>
                        <div style="font-size: 14px; color: #27272A;">{{ $r->protokol->data_nastepnej_wizyty->translatedFormat('j F Y') }}</div>
                    </td>
                </tr>
            @endif
        @endif
        @if ($r->protokol?->koszt_koncowy !== null)
            <tr>
                <td style="padding: 14px 18px 4px; font-size: 14px; color: #52525B;">Wg cennika</td>
                <td align="right" style="padding: 14px 18px 4px; font-size: 14px; color: #52525B; white-space: nowrap;">
                    {{ number_format($r->laczna_cena, 2, ',', ' ') }} zł
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 18px 14px; font-size: 14px; color: #52525B;">Koszt końcowy</td>
                <td align="right" style="padding: 4px 18px 14px; font-size: 18px; font-weight: 700; color: #18181B; white-space: nowrap;">
                    {{ number_format($r->protokol?->koszt_koncowy, 2, ',', ' ') }} zł
                </td>
            </tr>
        @else
            <tr>
                <td style="padding: 14px 18px; font-size: 14px; color: #52525B;">Razem wg cennika</td>
                <td align="right" style="padding: 14px 18px; font-size: 18px; font-weight: 700; color: #18181B; white-space: nowrap;">
                    {{ number_format($r->laczna_cena, 2, ',', ' ') }} zł
                </td>
            </tr>
        @endif
    </table>

    @include('emails.partials.przycisk', ['url' => $urlKolejna, 'tekst' => 'Umów kolejną wizytę'])

    <p style="margin: 0; font-size: 14px; line-height: 1.6; color: #52525B;">
        Pełną historię napraw znajdziesz w zakładce
        <a href="{{ $urlHistoria }}" target="_blank" style="color: #C2410C;">Moje pojazdy</a>.
    </p>
@endsection