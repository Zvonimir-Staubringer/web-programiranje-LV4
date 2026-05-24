# web-programiranje-LV4

Ova verzija projekta je prilagodena za `PHP + MySQL` i pokriva:

- zadatak `1.(a)` kroz autentikaciju, osobnu videoteka listu i admin upravljanje filmovima
- zadatak `2.` kroz galeriju slika s trajnim ocjenjivanjem 1-5 i prikazom prosjeka

## Struktura

- `index.php`, `images.php`, `pages/*.php` su glavne stranice
- `api/*.php` sadrzi PHP endpointove
- `includes/` sadrzi PDO konekciju, sesije i helper funkcije
- `public/` sadrzi CSS, JavaScript i slike
- `sql/schema.sql` sadrzi MySQL strukturu tablica

## Pokretanje u XAMPP-u

1. Kopiraj projekt u `htdocs`, npr. `C:\xampp\htdocs\WebLV4`
2. Pokreni `Apache` i `MySQL` u XAMPP Control Panelu
3. Po potrebi prilagodi `includes/config.php` ako MySQL nema `root` bez lozinke
4. Otvori `http://localhost/WebLV4/`

Aplikacija pri prvom otvaranju sama:

- kreira bazu `weblv4` ako postoji pristup za `CREATE DATABASE`
- kreira potrebne tablice
- seeda demo korisnike, filmove i slike

Ako tvoj MySQL korisnik nema pravo kreiranja baze, prvo rucno izvrsi `sql/schema.sql` u phpMyAdminu.

## Demo korisnici

- `student / student123`
- `admin / admin123`

## Napomena

- za LV4 predaju koristi se ova `PHP + MySQL` verzija projekta
