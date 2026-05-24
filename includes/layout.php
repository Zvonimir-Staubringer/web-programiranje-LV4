<?php

declare(strict_types=1);

function appBasePath(): string
{
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = str_contains($scriptName, '/pages/')
        ? dirname(dirname($scriptName))
        : dirname($scriptName);

    $basePath = rtrim($basePath, '/');
    return $basePath === '' ? '/' : $basePath;
}

function appUrl(string $path = ''): string
{
    $basePath = appBasePath();
    $trimmedPath = ltrim($path, '/');

    if ($trimmedPath === '') {
        return $basePath;
    }

    return ($basePath === '/' ? '' : $basePath) . '/' . $trimmedPath;
}

function renderPageStart(string $title, string $description, array $styles = [], array $scripts = []): void
{
    $basePath = appBasePath();
    ?>
<!DOCTYPE html>
<html lang="hr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>">
  <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=IBM+Plex+Sans:wght@300;400;600&display=swap" rel="stylesheet">
<?php foreach ($styles as $style): ?>
  <link rel="stylesheet" href="<?= htmlspecialchars(preg_match('~^(?:https?:)?//~', $style) ? $style : appUrl($style), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>
  <script>
    window.APP_BASE = <?= json_encode($basePath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="<?= htmlspecialchars(appUrl('public/js/cartState.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<?php foreach ($scripts as $script): ?>
  <script src="<?= htmlspecialchars(preg_match('~^(?:https?:)?//~', $script) ? $script : appUrl($script), ENT_QUOTES, 'UTF-8') ?>" defer></script>
<?php endforeach; ?>
</head>
<body>
  <header>
    <h1 class="header-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
  </header>
  <div id="notification" class="notification"></div>
<?php
}

function renderNavigation(): void
{
    ?>
  <nav aria-label="Primary navigation">
    <ul class="nav-menu">
      <li><a href="<?= htmlspecialchars(appUrl('index.php'), ENT_QUOTES, 'UTF-8') ?>">Home</a></li>
      <li><a href="<?= htmlspecialchars(appUrl('pages/graph.php'), ENT_QUOTES, 'UTF-8') ?>">Graphs</a></li>
      <li><a href="<?= htmlspecialchars(appUrl('images.php'), ENT_QUOTES, 'UTF-8') ?>">Images</a></li>
      <li><a href="<?= htmlspecialchars(appUrl('pages/cart.php'), ENT_QUOTES, 'UTF-8') ?>">My Library (<span id="cartCount">0</span>)</a></li>
      <li id="dashboardNavItem" hidden><a href="<?= htmlspecialchars(appUrl('pages/dashboard.php'), ENT_QUOTES, 'UTF-8') ?>">Dashboard</a></li>
      <li class="nav-status">User: <span id="navUser">Guest</span></li>
      <li><button type="button" id="logoutButton" class="nav-button" hidden>Logout</button></li>
    </ul>
  </nav>
<?php
}

function renderPageEnd(): void
{
    ?>
</body>
</html>
<?php
}
