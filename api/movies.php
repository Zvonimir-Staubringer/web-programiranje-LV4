<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $userId = currentUserId();
    $genre = trim((string) ($_GET['genre'] ?? ''));
    $yearFrom = $_GET['yearFrom'] === '' ? 0 : (int) $_GET['yearFrom'];
    $yearTo = $_GET['yearTo'] === '' ? 9999 : (int) $_GET['yearTo'];
    $country = trim((string) ($_GET['country'] ?? ''));
    $ratingFrom = $_GET['ratingFrom'] === '' ? 0 : (float) $_GET['ratingFrom'];
    $ratingTo = $_GET['ratingTo'] === '' ? 10 : (float) $_GET['ratingTo'];
    $sortBy = (string) ($_GET['sortBy'] ?? '');

    $sql = 'SELECT m.id, m.title, m.genre, m.year, m.duration_min, m.rating, m.director, m.origin_country';
    if ($userId !== null) {
    $sql .= ',
        EXISTS(
            SELECT 1
            FROM wanted_movies wm
            WHERE wm.user_id = :user_id
            AND wm.movie_id = m.id
        ) AS in_library,

        EXISTS(
            SELECT 1
            FROM watched_movies w
            WHERE w.user_id = :user_id
            AND w.movie_id = m.id
        ) AS is_watched';
    } else {
        $sql .= ', 0 AS in_library, 0 AS is_watched';
    }

    $sql .= ' FROM movies m WHERE 1=1';
    $params = [];

    if ($userId !== null) {
        $params[':user_id'] = $userId;
    }

    if ($genre !== '') {
        $sql .= ' AND FIND_IN_SET(:genre, REPLACE(m.genre, ", ", ",")) > 0';
        $params[':genre'] = $genre;
    }

    if ($yearFrom > 0) {
        $sql .= ' AND m.year >= :year_from';
        $params[':year_from'] = $yearFrom;
    }

    if ($yearTo > 0) {
        $sql .= ' AND m.year <= :year_to';
        $params[':year_to'] = $yearTo;
    }

    if ($country !== '') {
        $sql .= ' AND LOWER(m.origin_country) LIKE :country';
        $params[':country'] = '%' . mb_strtolower($country) . '%';
    }

    $sql .= ' AND m.rating BETWEEN :rating_from AND :rating_to';
    $params[':rating_from'] = $ratingFrom;
    $params[':rating_to'] = $ratingTo;

    if ($sortBy === 'year') {
        $sql .= ' ORDER BY m.year ASC, m.title ASC';
    } elseif ($sortBy === 'rating') {
        $sql .= ' ORDER BY m.rating DESC, m.title ASC';
    } else {
        $sql .= ' ORDER BY m.title ASC';
    }

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    $movies = array_map(static function (array $movie): array {
        return [
            'id' => (int) $movie['id'],
            'Naslov' => $movie['title'],
            'Zanr' => $movie['genre'],
            'Godina' => (int) $movie['year'],
            'Trajanje_min' => (int) $movie['duration_min'],
            'Ocjena' => (float) $movie['rating'],
            'Rezisery' => $movie['director'],
            'Zemlja_porijekla' => $movie['origin_country'],
            'inLibrary' => (bool) $movie['in_library'],
            'lowRatingWarning' => (float) $movie['rating'] < 5.0,
            'isWatched' => (bool) $movie['is_watched'],
        ];
    }, $stmt->fetchAll());

    jsonResponse(['movies' => $movies]);
}

requireAdmin($pdo);
$input = readJsonInput();

if ($method === 'POST') {
    $movie = validateMoviePayload($input);
    $stmt = $pdo->prepare(
        'INSERT INTO movies (title, genre, year, duration_min, rating, director, origin_country)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $movie['Naslov'],
        $movie['Zanr'],
        $movie['Godina'],
        $movie['Trajanje_min'],
        $movie['Ocjena'],
        $movie['Rezisery'],
        $movie['Zemlja_porijekla'],
    ]);

    jsonResponse(['message' => 'Film je dodan.', 'id' => (int) $pdo->lastInsertId()], 201);
}

if ($method === 'PUT') {
    $movieId = (int) ($_GET['id'] ?? 0);
    if ($movieId <= 0) {
        jsonResponse(['message' => 'Nedostaje ID filma.'], 400);
    }

    $movie = validateMoviePayload($input);
    $stmt = $pdo->prepare(
        'UPDATE movies
         SET title = ?, genre = ?, year = ?, duration_min = ?, rating = ?, director = ?, origin_country = ?
         WHERE id = ?'
    );
    $stmt->execute([
        $movie['Naslov'],
        $movie['Zanr'],
        $movie['Godina'],
        $movie['Trajanje_min'],
        $movie['Ocjena'],
        $movie['Rezisery'],
        $movie['Zemlja_porijekla'],
        $movieId,
    ]);

    jsonResponse(['message' => 'Film je azuriran.']);
}

if ($method === 'DELETE') {
    $movieId = (int) ($_GET['id'] ?? 0);
    if ($movieId <= 0) {
        jsonResponse(['message' => 'Nedostaje ID filma.'], 400);
    }

    $stmt = $pdo->prepare('DELETE FROM movies WHERE id = ?');
    $stmt->execute([$movieId]);
    jsonResponse(['message' => 'Film je obrisan.']);
}

jsonResponse(['message' => 'Metoda nije podrzana.'], 405);

function validateMoviePayload(array $input): array
{
    $movie = [
        'Naslov' => trim((string) ($input['Naslov'] ?? '')),
        'Zanr' => trim((string) ($input['Zanr'] ?? '')),
        'Godina' => (int) ($input['Godina'] ?? 0),
        'Trajanje_min' => (int) ($input['Trajanje_min'] ?? 0),
        'Ocjena' => (float) ($input['Ocjena'] ?? 0),
        'Rezisery' => trim((string) ($input['Rezisery'] ?? '')),
        'Zemlja_porijekla' => trim((string) ($input['Zemlja_porijekla'] ?? '')),
    ];

    if ($movie['Naslov'] === '' || $movie['Zanr'] === '' || $movie['Rezisery'] === '' || $movie['Zemlja_porijekla'] === '') {
        jsonResponse(['message' => 'Sva polja su obavezna.'], 400);
    }

    if ($movie['Godina'] < 1900 || $movie['Godina'] > 2100) {
        jsonResponse(['message' => 'Godina mora biti izmedu 1900 i 2100.'], 400);
    }

    if ($movie['Trajanje_min'] < 30 || $movie['Trajanje_min'] > 400) {
        jsonResponse(['message' => 'Trajanje mora biti izmedu 30 i 400 minuta.'], 400);
    }

    if ($movie['Ocjena'] < 0 || $movie['Ocjena'] > 10) {
        jsonResponse(['message' => 'Ocjena mora biti izmedu 0 i 10.'], 400);
    }

    return $movie;
}
