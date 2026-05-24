<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/layout.php';

renderPageStart(
    'Admin Dashboard',
    'Administratorski dashboard za upravljanje filmovima i slikama.',
    ['public/styles/cart.css'],
    ['public/js/dashboard.js']
);
renderNavigation();
?>
  <main>
    <section id="accessDenied" class="cart-card" hidden>
      <h2>Pristup nije dozvoljen</h2>
      <p>Ova stranica dostupna je samo prijavljenom administratoru.</p>
    </section>

    <section id="adminPanel" hidden>
      <div class="cart-card">
        <h2>Upravljanje filmovima</h2>
        <form id="movieForm" class="dashboard-form">
          <input name="Naslov" placeholder="Naslov" required>
          <input name="Zanr" placeholder="Zanr" required>
          <input name="Godina" type="number" placeholder="Godina" required>
          <input name="Trajanje_min" type="number" placeholder="Trajanje (min)" required>
          <input name="Ocjena" type="number" step="0.1" min="0" max="10" placeholder="Ocjena" required>
          <input name="Rezisery" placeholder="Redatelj" required>
          <input name="Zemlja_porijekla" placeholder="Zemlja" required>
          <button type="submit" id="movieSubmitLabel">Dodaj film</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Year</th>
              <th>Duration</th>
              <th>Rating</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="movieAdminBody"></tbody>
        </table>
      </div>

      <div class="cart-card">
        <h2>Upravljanje slikama</h2>
        <form id="imageForm" class="dashboard-form">
          <input id="imageTitle" name="title" placeholder="Naziv slike" required>
          <input id="imageDescription" name="description" placeholder="Opis slike">
          <input id="imageFile" name="file" type="file" accept="image/png, image/jpeg" required>
          <button type="submit">Dodaj sliku</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Source</th>
              <th>Average</th>
              <th>Votes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="imageAdminBody"></tbody>
        </table>
      </div>
    </section>
  </main>
  <footer>
    <p>&copy; 2026. Web Programming. All rights reserved.</p>
  </footer>
<?php renderPageEnd(); ?>
