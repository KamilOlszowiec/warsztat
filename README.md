# Warsztat – rezerwacje wizyt w warsztacie samochodowym

Aplikacja internetowa do zarządzania rezerwacjami w warsztacie samochodowym, wykonana w ramach pracy inżynierskiej
(Politechnika Lubelska, kierunek informatyka, promotor: dr Beata Pańczyk).

## Funkcje

- **Klient:** rezerwacja wizyty w kreatorze (usługi, termin, opcjonalny wybór mechanika, pojazd), zmiana terminu
  i anulowanie, pojazdy z historią napraw i protokołami z wizyt, powiadomienia e-mail.
- **Pracownik (mechanik):** plan dnia z kolumnami mechaników, obsługa własnych wizyt (zatwierdzanie, odrzucanie,
  zmiana terminu, zakończenie z protokołem), przyjmowanie wizyt telefonicznych, wyszukiwarka klientów i pojazdów, własny grafik.
- **Administrator:** konta użytkowników, usługi i specjalizacje, grafik mechaników, godziny pracy i dni wolne warsztatu,
  przydział mechanika do wizyty, panel z alertami.

Wolne terminy są wyznaczane automatycznie na podstawie godzin otwarcia, dni wolnych, grafiku i specjalizacji mechaników
oraz przyjętych już rezerwacji. Zapis rezerwacji odbywa się w transakcji z blokadą, co chroni przed podwójną rezerwacją terminu.

## Technologie

PHP 8.2, Laravel 12, MySQL / MariaDB, Blade, Tailwind CSS 4 (Vite), JavaScript, Resend (e-mail), PHPUnit.

## Instalacja

Wymagania: PHP 8.2+, Composer, Node.js 20.19+, serwer MySQL lub MariaDB.

```bash
git clone https://github.com/DemoneuszSosik/warsztat.git
cd warsztat
composer install
npm install
npm run build
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
```

Utwórz pustą bazę `warsztat_db` i uzupełnij dane dostępowe w pliku `.env`, a następnie:

```bash
php artisan migrate --seed
php artisan serve
```

Aplikacja działa pod adresem http://localhost:8000. Domyślnie wiadomości e-mail są zapisywane w `storage/logs/laravel.log`;
prawdziwą wysyłkę włącza się ustawieniem `MAIL_MAILER=resend` i kluczem `RESEND_API_KEY`.

### Konta demonstracyjne (hasło: `haslo1234`)

| Rola | E-mail |
| --- | --- |
| Administrator | admin@warsztat.pl |
| Pracownik | piotr@warsztat.pl |
| Klient | jan@example.com |

## Testy

Testy korzystają z osobnej bazy `warsztat_test` (należy ją utworzyć wcześniej):

```bash
php artisan config:clear
php artisan test
```
