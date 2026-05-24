# web-programiranje-LV4

## Struktura

- `index.php`, `images.php`, `pages/*.php` su glavne stranice
- `api/*.php` sadrzi PHP endpointove
- `includes/` sadrzi PDO konekciju, sesije i helper funkcije
- `public/` sadrzi CSS, JavaScript i slike
- `sql/schema.sql` sadrzi MySQL strukturu tablica

## Pokretanje u XAMPP-u

1. Kopirati projekt u `htdocs`, npr. `C:\xampp\htdocs\WebLV4`
2. Pokreniti `Apache` i `MySQL` u XAMPP Control Panelu
3. Po potrebi prilagoditi `includes/config.php` ako MySQL nema `root` bez lozinke
4. Otvoriti `http://localhost/WebLV4/`

Ako MySQL korisnik nema pravo kreiranja baze, prvo rucno izvrsiti `sql/schema.sql` u phpMyAdminu.

## Demo korisnici

- `student / student123`
- `admin / admin123`

