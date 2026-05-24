<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/layout.php';

renderPageStart(
    'Movie Charts',
    'Vizualni prikaz podataka o filmovima.',
    ['public/styles/style_grafikon.css'],
    ['https://cdn.jsdelivr.net/npm/chart.js', 'public/js/graph.js']
);
renderNavigation();
?>
  <main>
    <section class="chart-section">
      <h2>Movies by Genre</h2>
      <canvas id="genreChart"></canvas>
    </section>

    <section class="chart-section-bar">
      <h2>Average Rating per Genre</h2>
      <canvas id="ratingChart"></canvas>
    </section>
  </main>

  <footer>
    <p>&copy; 2026. Web Programming. All rights reserved.</p>
  </footer>
<?php renderPageEnd(); ?>
