/*
 * Wspólne elementy formularzy rezerwacji:
 * formatowanie dat, kalendarz, siatka wolnych godzin, lista mechaników i kroki kreatora.
 *
 * Używane przez: kreator rezerwacji klienta, zmianę terminu (klient i pracownik)
 * oraz kreator nowej wizyty przyjmowanej przez pracownika.
 * Moduł udostępnia funkcje jako window.Rezerwacje, z którego korzystają skrypty widoków.
 */

// ---------- Formatowanie ----------

const NAZWY_DNI = ['niedziela', 'poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota'];

const pad = n => String(n).padStart(2, '0');

export const format = {
    // Date → '2026-11-17'
    naTekst: d => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()),

    // '2026-11-17' → Date (czas lokalny, bez przesunięć strefy)
    zTekstu(s) {
        const [r, m, d] = s.split('-').map(Number);
        return new Date(r, m - 1, d);
    },

    // 90 → '1 h 30 min'
    czas(minuty) {
        if (minuty < 60) return minuty + ' min';
        const h = Math.floor(minuty / 60);
        const m = minuty % 60;
        return h + ' h' + (m ? ' ' + m + ' min' : '');
    },

    // '2026-11-17' → 'wtorek, 17.11.2026'
    data(s) {
        const d = format.zTekstu(s);
        return NAZWY_DNI[d.getDay()] + ', ' + pad(d.getDate()) + '.' + pad(d.getMonth() + 1) + '.' + d.getFullYear();
    },

    // ('09:30', 90) → '11:00'
    dodajMinuty(godzina, minuty) {
        const [h, m] = godzina.split(':').map(Number);
        const suma = h * 60 + m + minuty;
        return pad(Math.floor(suma / 60)) + ':' + pad(suma % 60);
    },

    kwota: liczba => liczba.toFixed(2) + ' zł',
};

const poczatekMiesiaca = s => {
    const d = format.zTekstu(s);
    return new Date(d.getFullYear(), d.getMonth(), 1);
};

// ---------- Kalendarz ----------

/**
 * Kalendarz miesięczny z wyszarzonymi dniami zamkniętymi, dniami wolnymi
 * i dniami spoza okresu rezerwacji. Znacznik: komponent <x-kalendarz />.
 *
 * @param {HTMLElement} element  kontener komponentu
 * @param {HTMLInputElement} pole  ukryte pole z wybraną datą (Y-m-d)
 * @param {object} ustawienia  { dzisiaj, maksData, dniZamkniete, dniWolne, poWyborze(data) }
 */
export function kalendarz(element, pole, { dzisiaj, maksData, dniZamkniete, dniWolne, poWyborze }) {
    const dni = element.querySelector('[data-kal="dni"]');
    const naglowek = element.querySelector('[data-kal="miesiac"]');
    const poprzedni = element.querySelector('[data-kal="poprzedni"]');
    const nastepny = element.querySelector('[data-kal="nastepny"]');

    let miesiac = poczatekMiesiaca(pole.value || dzisiaj);

    function renderuj() {
        dni.innerHTML = '';
        naglowek.textContent = miesiac.toLocaleDateString('pl-PL', { month: 'long', year: 'numeric' });

        // Puste pola przed 1. dniem miesiąca (tydzień od poniedziałku)
        const przesuniecie = (miesiac.getDay() + 6) % 7;
        for (let i = 0; i < przesuniecie; i++) dni.appendChild(document.createElement('span'));

        const ileDni = new Date(miesiac.getFullYear(), miesiac.getMonth() + 1, 0).getDate();

        for (let d = 1; d <= ileDni; d++) {
            const data = new Date(miesiac.getFullYear(), miesiac.getMonth(), d);
            const tekst = format.naTekst(data);
            const iso = data.getDay() === 0 ? 7 : data.getDay();
            const dzienWolny = dniWolne.includes(tekst);
            const niedostepny = tekst < dzisiaj || tekst > maksData || dniZamkniete.includes(iso) || dzienWolny;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.textContent = d;
            btn.dataset.data = tekst;
            btn.disabled = niedostepny;
            if (dzienWolny) btn.title = 'Dzień wolny warsztatu';

            let klasy = 'h-9 text-sm transition ';
            if (tekst === pole.value) klasy += 'bg-orange-600 text-white font-semibold';
            else if (niedostepny) klasy += 'text-zinc-700 cursor-not-allowed' + (dzienWolny ? ' line-through' : '');
            else klasy += 'text-zinc-200 hover:bg-white/10';
            if (tekst === dzisiaj && tekst !== pole.value) klasy += ' ring-1 ring-inset ring-zinc-600';

            btn.className = klasy;
            btn.style.borderRadius = '2px';
            dni.appendChild(btn);
        }

        poprzedni.disabled = miesiac <= poczatekMiesiaca(dzisiaj);
        nastepny.disabled = miesiac >= poczatekMiesiaca(maksData);
    }

    dni.addEventListener('click', e => {
        const btn = e.target.closest('button[data-data]');
        if (!btn || btn.disabled) return;
        pole.value = btn.dataset.data;
        renderuj();
        poWyborze?.(pole.value);
    });
    poprzedni.addEventListener('click', () => {
        miesiac = new Date(miesiac.getFullYear(), miesiac.getMonth() - 1, 1);
        renderuj();
    });
    nastepny.addEventListener('click', () => {
        miesiac = new Date(miesiac.getFullYear(), miesiac.getMonth() + 1, 1);
        renderuj();
    });

    renderuj();
    return { renderuj };
}

// ---------- Siatka wolnych godzin ----------

/**
 * Pobiera wolne godziny z serwera i wyświetla je jako kafelki do wyboru.
 * Znacznik: komponent <x-siatka-godzin />.
 *
 * @param {HTMLElement} element  kontener komponentu
 * @param {object} ustawienia
 *   url        – adres endpointu JSON zwracającego { godziny, komunikat }
 *   parametry  – funkcja zwracająca URLSearchParams albo tekst komunikatu, gdy zapytanie nie ma sensu
 *   poZmianie  – wywoływana po każdej zmianie listy lub wyboru godziny
 *   opis       – opcjonalna funkcja zwracająca opis nad siatką (np. dzień i czas wizyty)
 *   dopisek    – opcjonalna funkcja (godzina) → tekst dopisywany do kafelka, np. „•” przy obecnym terminie
 *   zaznacz    – godzina do zaznaczenia po pierwszym wczytaniu (np. po błędzie walidacji)
 */
export function siatkaGodzin(element, { url, parametry, poZmianie, opis, dopisek, zaznacz = null }) {
    const sloty = element.querySelector('[data-sloty="lista"]');
    const komunikat = element.querySelector('[data-sloty="komunikat"]');
    const opisElement = element.querySelector('[data-sloty="opis"]');

    let numerZapytania = 0;
    let doZaznaczenia = zaznacz;

    const wybrana = () => sloty.querySelector('input[name="godzina"]:checked')?.value ?? null;

    function pokazKomunikat(tekst) {
        sloty.innerHTML = '';
        komunikat.textContent = tekst;
        komunikat.classList.remove('hidden');
        poZmianie?.();
    }

    function renderuj(godziny) {
        sloty.innerHTML = '';
        komunikat.classList.add('hidden');

        godziny.forEach(godzina => {
            const label = document.createElement('label');
            label.className = 'cursor-pointer';

            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'godzina';
            input.value = godzina;
            input.className = 'peer sr-only';
            if (godzina === doZaznaczenia) input.checked = true;

            const kafelek = document.createElement('div');
            kafelek.className = 'text-center text-sm py-2 border border-zinc-700 text-zinc-300 hover:border-zinc-500 '
                + 'peer-checked:bg-orange-600 peer-checked:border-orange-600 peer-checked:text-white transition';
            kafelek.style.borderRadius = '2px';
            kafelek.textContent = godzina + (dopisek?.(godzina) ?? '');

            label.append(input, kafelek);
            sloty.appendChild(label);
        });

        doZaznaczenia = null;
        poZmianie?.();
    }

    async function pobierz() {
        if (opisElement) opisElement.textContent = opis?.() ?? '';

        const params = parametry();
        if (typeof params === 'string') {
            pokazKomunikat(params);
            return;
        }

        // Przy ponownym pobraniu (np. zmiana mechanika) zachowujemy wybraną godzinę, jeśli nadal jest wolna
        doZaznaczenia = wybrana() ?? doZaznaczenia;
        const numer = ++numerZapytania;
        pokazKomunikat('Szukam wolnych terminów…');

        try {
            const odp = await fetch(url + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            const dane = await odp.json();

            // Odpowiedź na starsze zapytanie – wybór w międzyczasie się zmienił
            if (numer !== numerZapytania) return;

            if (!odp.ok) {
                const bledy = dane.errors ? Object.values(dane.errors).flat() : [];
                pokazKomunikat(bledy[0] || 'Nie udało się pobrać terminów. Spróbuj ponownie.');
            } else if (dane.godziny.length === 0) {
                pokazKomunikat(dane.komunikat || 'Brak wolnych terminów w wybranym dniu.');
            } else {
                renderuj(dane.godziny);
            }
        } catch (e) {
            if (numer === numerZapytania) {
                pokazKomunikat('Nie udało się pobrać terminów. Sprawdź połączenie i spróbuj ponownie.');
            }
        }
    }

    sloty.addEventListener('change', () => poZmianie?.());

    return { pobierz, wybrana, pokazKomunikat };
}

// ---------- Lista mechaników ----------

/**
 * Uzupełnia pole wyboru mechanikami zdolnymi wykonać wybrane usługi.
 * Zachowuje dotychczasowy wybór, jeśli mechanik nadal jest na liście.
 *
 * @param {HTMLSelectElement} pole  pole wyboru z opcją „dowolny” (value="")
 * @param {object} ustawienia  { url, parametry() → URLSearchParams|null, poZmianieWyboru(), poczatkowy }
 */
export function listaMechanikow(pole, { url, parametry, poZmianieWyboru, poczatkowy = null }) {
    let numerZapytania = 0;
    let doZaznaczenia = poczatkowy;

    async function pobierz() {
        const przed = pole.value;
        const zachowaj = przed || doZaznaczenia || '';
        const params = parametry();
        const numer = ++numerZapytania;

        if (!params) {
            pole.querySelectorAll('option:not([value=""])').forEach(o => o.remove());
            pole.value = '';
            if (przed) poZmianieWyboru?.();
            return;
        }

        try {
            const odp = await fetch(url + '?' + params.toString(), { headers: { Accept: 'application/json' } });
            const dane = await odp.json();
            if (numer !== numerZapytania || !odp.ok) return;

            pole.querySelectorAll('option:not([value=""])').forEach(o => o.remove());
            dane.mechanicy.forEach(m => pole.add(new Option(m.nazwa, m.id)));

            const nadal = dane.mechanicy.some(m => String(m.id) === String(zachowaj));
            pole.value = nadal ? String(zachowaj) : '';
            doZaznaczenia = null;

            if (pole.value !== przed) poZmianieWyboru?.();
        } catch (e) {
            // Brak listy nie blokuje rezerwacji – zostaje „dowolny mechanik”
        }
    }

    pole.addEventListener('change', () => poZmianieWyboru?.());

    return {
        pobierz,
        opis: () => (pole.value ? pole.options[pole.selectedIndex].textContent : 'Dowolny'),
    };
}

// ---------- Kroki kreatora ----------

/**
 * Obsługa kreatora wieloetapowego: pasek kroków (<x-kroki />), sekcje [data-krok],
 * przyciski Wstecz / Dalej / Zatwierdź oraz przechodzenie tylko do kroków,
 * których poprzedniki są kompletne.
 *
 * @param {HTMLFormElement} formularz
 * @param {object} ustawienia  { liczba, gotowy(n), poWejsciu(n), wstecz, dalej, zatwierdz, tekstWysylania }
 */
export function kroki(formularz, { liczba, gotowy, poWejsciu, wstecz, dalej, zatwierdz, tekstWysylania = 'Wysyłanie…' }) {
    let krok = 1;

    const moznaWejsc = n => {
        for (let i = 1; i < n; i++) if (!gotowy(i)) return false;
        return true;
    };

    function odswiez() {
        const licznik = document.querySelector('[data-krok-licznik]');
        if (licznik) licznik.textContent = 'KROK ' + krok + ' Z ' + liczba;

        formularz.querySelectorAll('section[data-krok]').forEach(s => {
            s.classList.toggle('hidden', Number(s.dataset.krok) !== krok);
        });

        document.querySelectorAll('[data-krok-wskaznik]').forEach(b => {
            const n = Number(b.dataset.krokWskaznik);
            b.querySelector('.krok-kolko').className = 'krok-kolko w-9 h-9 flex items-center justify-center text-sm font-semibold border transition '
                + (n <= krok ? 'bg-orange-600 border-orange-600 text-white' : 'bg-[#1F2128] border-zinc-700 text-zinc-500');
            b.querySelector('.krok-nazwa').className = 'krok-nazwa text-xs ' + (n === krok ? 'text-zinc-100 font-medium' : 'text-zinc-500');
            b.disabled = !moznaWejsc(n);
        });

        document.querySelectorAll('[data-krok-linia]').forEach(l => {
            l.className = 'flex-1 h-0.5 mx-2 mb-5 transition ' + (Number(l.dataset.krokLinia) < krok ? 'bg-orange-600' : 'bg-zinc-800');
        });

        wstecz.classList.toggle('invisible', krok === 1);
        dalej.classList.toggle('hidden', krok === liczba);
        zatwierdz.classList.toggle('hidden', krok !== liczba);
        dalej.disabled = !gotowy(krok);
    }

    function przejdzDo(n) {
        if (n < 1 || n > liczba || !moznaWejsc(n)) return;
        krok = n;
        poWejsciu?.(krok);
        odswiez();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    dalej.addEventListener('click', () => { if (gotowy(krok)) przejdzDo(krok + 1); });
    wstecz.addEventListener('click', () => przejdzDo(krok - 1));
    document.querySelectorAll('[data-krok-wskaznik]').forEach(b => {
        b.addEventListener('click', () => przejdzDo(Number(b.dataset.krokWskaznik)));
    });

    // Enter w polach (poza polami tekstowymi wielowierszowymi) nie wysyła formularza przedwcześnie
    formularz.addEventListener('keydown', e => {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') e.preventDefault();
    });

    formularz.addEventListener('submit', e => {
        if (krok !== liczba || !moznaWejsc(liczba)) {
            e.preventDefault();
            return;
        }
        // Ochrona przed podwójnym wysłaniem
        zatwierdz.disabled = true;
        zatwierdz.textContent = tekstWysylania;
    });

    return {
        przejdzDo,
        odswiez,
        moznaWejsc,
        get krok() { return krok; },
        // Po błędzie walidacji: najdalszy krok nie dalej niż wskazany, do którego można wejść
        otworz(cel) {
            krok = 1;
            for (let n = 1; n <= cel; n++) if (moznaWejsc(n)) krok = n;
            poWejsciu?.(krok);
            odswiez();
        },
    };
}

window.Rezerwacje = { format, kalendarz, siatkaGodzin, listaMechanikow, kroki };
