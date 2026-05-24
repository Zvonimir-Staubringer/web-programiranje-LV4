<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/layout.php';

renderPageStart(
    'My Library',
    'Osobna videoteka prijavljenog korisnika.',
    ['public/styles/cart.css'],
    ['public/js/cart.js']
);
renderNavigation();
?>
  <main>
    <div class="cart-card">
      <p class="section-intro" data-session-summary>Niste prijavljeni.</p>
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Year</th>
            <th>Rating</th>
            <th>Remove</th>
          </tr>
        </thead>
        <tbody id="cartBody"></tbody>
      </table>

      <p id="emptyLibrary" class="empty-state" hidden>Vasa videoteka je prazna.</p>
      <button id="confirm" type="button">Confirm Weekend Marathon</button>
    </div>
  </main>
  <footer>
    <p>&copy; 2026. Web Programming. All rights reserved.</p>
  </footer>
<?php renderPageEnd(); ?>
