<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

renderPageStart(
    'Movie Gallery',
    'Movie image gallery with persistent user ratings.',
    ['public/styles/style_images.css'],
    ['public/js/gallery.js']
);
renderNavigation();
?>
  <main>
    <section class="gallery-section">
      <h2>Ocjenjivanje fotografija</h2>
      <p id="ratingAccessHint" class="gallery-intro">Za ocjenjivanje slika potrebna je prijava.</p>
      <p id="emptyGallery" hidden>Trenutno nema dostupnih slika.</p>
      <div id="gallery" class="gallery" role="list"></div>
    </section>

    <section class="gallery-section ratings-section">
      <h2>Moje ocjene</h2>
      <table class="ratings-table">
        <thead>
          <tr>
            <th>Slika</th>
            <th>Moja ocjena</th>
            <th>Zadnja izmjena</th>
          </tr>
        </thead>
        <tbody id="myRatingsBody"></tbody>
      </table>
      <p id="emptyRatings">Prijavite se kako biste vidjeli vlastite ocjene.</p>
    </section>
  </main>

  <footer>
    <p>&copy; 2026. Web Programming. All rights reserved.</p>
  </footer>
<?php renderPageEnd(); ?>
