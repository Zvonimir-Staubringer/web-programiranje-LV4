<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

renderPageStart(
    'Movie Database',
    'Movie Database aplikacija s autenticiranjem, osobnom videotekom i galerijom slika.',
    ['public/styles/style.css'],
    ['public/js/app.js']
);
renderNavigation();
?>
  <main>
    <section class="content-section auth-layout" aria-labelledby="auth-title">
      <div class="auth-summary-card">
        <h2 id="auth-title">Autentikacija i osobna videoteka</h2>
        <p id="sessionSummary">
          Prijavite se ili kreirajte korisnicki racun za spremanje filmova i ocjenjivanje slika.
        </p>
        <p id="guestHint">Demo korisnici: <strong>student / student123</strong> i <strong>admin / admin123</strong>.</p>
        <div id="userActions" hidden>
          <a href="<?= htmlspecialchars(appUrl('pages/cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cta-button">Otvori moju videoteka listu</a>
        </div>
      </div>

      <div class="auth-forms">
        <form id="loginForm" class="auth-form" data-guest-only method="post">
          <h3>Prijava</h3>
          <label for="loginUsername">Korisnicko ime</label>
          <input id="loginUsername" name="username" type="text" required>
          <label for="loginPassword">Lozinka</label>
          <input id="loginPassword" name="password" type="password" required>
          <button type="submit">Prijavi se</button>
        </form>

        <form id="registerForm" class="auth-form" data-guest-only method="post">
          <h3>Registracija</h3>
          <label for="registerUsername">Korisnicko ime</label>
          <input id="registerUsername" name="username" type="text" required>
          <label for="registerPassword">Lozinka</label>
          <input id="registerPassword" name="password" type="password" required>
          <label for="registerConfirmPassword">Potvrda lozinke</label>
          <input id="registerConfirmPassword" name="confirmPassword" type="password" required>
          <button type="submit">Kreiraj racun</button>
        </form>
      </div>
    </section>

    <section class="content-section" aria-labelledby="movie-titles">
      <h2 id="movie-titles">List of Movies</h2>
      <div class="filters">
        <select id="genreFilter">
          <option value="">All Genres</option>
        </select>
        <input type="number" id="yearFrom" placeholder="Year from">
        <input type="number" id="yearTo" placeholder="Year to">
        <input type="text" id="countryFilter" placeholder="Country">
        <input type="number" id="ratingFrom" placeholder="Rating from" step="0.1" min="0" max="10">
        <input type="number" id="ratingTo" placeholder="Rating to" step="0.1" min="0" max="10">
        <button id="applyFilters" type="button">Apply Filters</button>
        <select id="sortBy">
          <option value="">Sort By</option>
          <option value="year">Year</option>
          <option value="rating">Rating</option>
        </select>
        <a href="<?= htmlspecialchars(appUrl('pages/cart.php'), ENT_QUOTES, 'UTF-8') ?>" class="cta-button">View My Library</a>
      </div>

      <div class="table-image-layout">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Year</th>
                <th>Genre</th>
                <th>Duration (min)</th>
                <th>Country</th>
                <th>Rating</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="movieTableBody"></tbody>
          </table>
        </div>

        <aside class="image-aside" aria-label="Featured movie">
          <figure>
            <picture>
              <source srcset="<?= htmlspecialchars(appUrl('public/images/magnolia.jpg'), ENT_QUOTES, 'UTF-8') ?>" media="(min-width: 768px)">
              <source srcset="<?= htmlspecialchars(appUrl('public/images/sentimental_value.jpg'), ENT_QUOTES, 'UTF-8') ?>" media="(max-width: 767px)">
              <img src="<?= htmlspecialchars(appUrl('public/images/magnolia.jpg'), ENT_QUOTES, 'UTF-8') ?>" alt="Magnolia" aria-describedby="image-description" class="featured-image">
            </picture>
            <figcaption id="image-description">Magnolia movie poster.</figcaption>
          </figure>
        </aside>
      </div>
    </section>

    <section class="content-section fade-in" aria-labelledby="about-page">
      <h2 id="about-page">Sto je implementirano za LV4</h2>
      <p>
        Filmovi se filtriraju na serveru kroz PHP i MySQL, odabrani zapisi spremaju se u osobnu videoteka listu po korisniku,
        a slike se mogu trajno ocjenjivati ocjenama od 1 do 5.
      </p>
    </section>

    <article class="content-section slide-in" aria-labelledby="fun-facts">
      <h2 id="fun-facts">Fun Facts</h2>
      <p>
        Administratorski dashboard omogucuje dodavanje, uredivanje i brisanje filmova te unos novih slika
        uz validaciju JPEG/PNG formata i ogranicenje od 5 MB.
      </p>
    </article>
  </main>

  <footer>
    <p>&copy; 2026. Web Programming. All rights reserved.</p>
  </footer>
<?php renderPageEnd(); ?>
