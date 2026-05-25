<?php

declare(strict_types=1);

require_once __DIR__ . '/utils.php';

function getPdo(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    $serverDsn = sprintf(
        'mysql:host=%s;port=%d;charset=utf8mb4',
        $config['db_host'],
        $config['db_port']
    );

    $serverPdo = new PDO($serverDsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $serverPdo->exec(sprintf(
        'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
        str_replace('`', '``', $config['db_name'])
    ));

    $dbDsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    $pdo = new PDO($dbDsn, $config['db_user'], $config['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    initializeDatabase($pdo);
    return $pdo;
}

function initializeDatabase(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('user','admin') NOT NULL DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS movies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            genre VARCHAR(255) NOT NULL,
            year SMALLINT NOT NULL,
            duration_min SMALLINT NOT NULL,
            rating DECIMAL(3,1) NOT NULL,
            director VARCHAR(255) NOT NULL,
            origin_country VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS wanted_movies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            movie_id INT UNSIGNED NOT NULL,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_movie (user_id, movie_id),
            CONSTRAINT fk_wanted_movies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_wanted_movies_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS watched_movies (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            movie_id INT UNSIGNED NOT NULL,
            watched_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_watched_user_movie (user_id, movie_id),
            CONSTRAINT fk_watched_movies_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_watched_movies_movie FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS images (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(255) NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NULL,
            source ENUM('local','upload') NOT NULL DEFAULT 'local',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS ratings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            image_id INT UNSIGNED NOT NULL,
            rating TINYINT UNSIGNED NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_image (user_id, image_id),
            CONSTRAINT fk_ratings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            CONSTRAINT fk_ratings_image FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    seedUsers($pdo);
    seedMovies($pdo);
    repairSeededMovies($pdo);
    seedImages($pdo);

    $initialized = true;
}

function seedUsers(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)');
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'admin']);
    $stmt->execute(['student', password_hash('student123', PASSWORD_DEFAULT), 'user']);
}

function seedMovies(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM movies')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $csvPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'movies.csv';
    if (!is_file($csvPath)) {
        return;
    }

    $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false || count($lines) < 2) {
        return;
    }

    $headers = array_map('stripUtf8Bom', parseCsvLine(array_shift($lines)));
    $stmt = $pdo->prepare(
        'INSERT INTO movies (title, genre, year, duration_min, rating, director, origin_country)
         VALUES (:title, :genre, :year, :duration, :rating, :director, :country)'
    );

    foreach ($lines as $line) {
        $values = parseCsvLine($line);
        $row = array_combine($headers, array_pad($values, count($headers), ''));
        if (!is_array($row)) {
            continue;
        }

        $stmt->execute([
            ':title' => $row['Naslov'] ?? '',
            ':genre' => $row['Zanr'] ?? '',
            ':year' => (int) ($row['Godina'] ?? 0),
            ':duration' => (int) ($row['Trajanje_min'] ?? 0),
            ':rating' => (float) ($row['Ocjena'] ?? 0),
            ':director' => $row['Rezisery'] ?? '',
            ':country' => $row['Zemlja_porijekla'] ?? '',
        ]);
    }
}

function repairSeededMovies(PDO $pdo): void
{
    $blankTitleCount = (int) $pdo->query("SELECT COUNT(*) FROM movies WHERE title = ''")->fetchColumn();
    if ($blankTitleCount === 0) {
        return;
    }

    $csvPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'movies.csv';
    if (!is_file($csvPath)) {
        return;
    }

    $lines = file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false || count($lines) < 2) {
        return;
    }

    $headers = array_map('stripUtf8Bom', parseCsvLine(array_shift($lines)));
    $seedRows = [];

    foreach ($lines as $line) {
        $values = parseCsvLine($line);
        $row = array_combine($headers, array_pad($values, count($headers), ''));
        if (!is_array($row) || trim((string) ($row['Naslov'] ?? '')) === '') {
            continue;
        }

        $seedRows[] = [
            'title' => trim((string) $row['Naslov']),
            'genre' => trim((string) ($row['Zanr'] ?? '')),
            'year' => (int) ($row['Godina'] ?? 0),
            'duration' => (int) ($row['Trajanje_min'] ?? 0),
            'rating' => (float) ($row['Ocjena'] ?? 0),
            'director' => trim((string) ($row['Rezisery'] ?? '')),
            'country' => trim((string) ($row['Zemlja_porijekla'] ?? '')),
        ];
    }

    if ($seedRows === []) {
        return;
    }

    $blankMoviesStmt = $pdo->query("SELECT id FROM movies WHERE title = '' ORDER BY id ASC");
    $blankMovieIds = array_map(static fn (array $row): int => (int) $row['id'], $blankMoviesStmt->fetchAll());

    $updateStmt = $pdo->prepare(
        'UPDATE movies
         SET title = :title,
             genre = :genre,
             year = :year,
             duration_min = :duration,
             rating = :rating,
             director = :director,
             origin_country = :country
         WHERE id = :id'
    );

    foreach ($blankMovieIds as $index => $movieId) {
        if (!isset($seedRows[$index])) {
            break;
        }

        $row = $seedRows[$index];
        $updateStmt->execute([
            ':id' => $movieId,
            ':title' => $row['title'],
            ':genre' => $row['genre'],
            ':year' => $row['year'],
            ':duration' => $row['duration'],
            ':rating' => $row['rating'],
            ':director' => $row['director'],
            ':country' => $row['country'],
        ]);
    }
}

function seedImages(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM images')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $seedImages = [
        ['public/images/Batman.jpg', 'Batman', 'Batman movie poster.'],
        ['public/images/La_La_Land_(film).png', 'La La Land', 'La La Land movie poster.'],
        ['public/images/forestgump.jpg', 'Forrest Gump', 'Forrest Gump movie poster.'],
        ['public/images/captainamerica.jpg', 'Captain America', 'Captain America poster.'],
        ['public/images/soul.jpg', 'Soul', 'Soul movie poster.'],
        ['public/images/harry_potter.jpg', 'Harry Potter', 'Harry Potter movie poster.'],
        ['public/images/magnolia.jpg', 'Magnolia', 'Magnolia featured poster.'],
        ['public/images/sentimental_value.jpg', 'Sentimental Value', 'Alternative featured image.'],
    ];

    $stmt = $pdo->prepare('INSERT INTO images (file_path, title, description, source) VALUES (?, ?, ?, "local")');
    foreach ($seedImages as [$filePath, $title, $description]) {
        $stmt->execute([$filePath, $title, $description]);
    }
}

function getSessionSummary(PDO $pdo, ?int $userId): array
{
    if ($userId === null) {
        return [
            'authenticated' => false,
            'user' => null,
            'libraryCount' => 0,
            'ratingCount' => 0,
        ];
    }

    $userStmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = ?');
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user) {
        return [
            'authenticated' => false,
            'user' => null,
            'libraryCount' => 0,
            'ratingCount' => 0,
        ];
    }

    $libraryStmt = $pdo->prepare('SELECT COUNT(*) FROM wanted_movies WHERE user_id = ?');
    $libraryStmt->execute([$userId]);

    $ratingStmt = $pdo->prepare('SELECT COUNT(*) FROM ratings WHERE user_id = ?');
    $ratingStmt->execute([$userId]);

    return [
        'authenticated' => true,
        'user' => [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'role' => $user['role'],
        ],
        'libraryCount' => (int) $libraryStmt->fetchColumn(),
        'ratingCount' => (int) $ratingStmt->fetchColumn(),
    ];
}
