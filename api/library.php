<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$userId = requireLogin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $wantedStmt = $pdo->prepare(
        'SELECT m.id, m.title, m.genre, m.year, m.duration_min, m.rating, m.director, m.origin_country
         FROM wanted_movies wm
         INNER JOIN movies m ON m.id = wm.movie_id
         WHERE wm.user_id = ?
         ORDER BY wm.added_at DESC'
    );
    $wantedStmt->execute([$userId]);
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
        ];
    }, $wantedStmt->fetchAll());

    $watchedStmt = $pdo->prepare(
        'SELECT m.id, m.title, m.genre, m.year, m.duration_min, m.rating, m.director, m.origin_country, wm.watched_at
         FROM watched_movies wm
         INNER JOIN movies m ON m.id = wm.movie_id
         WHERE wm.user_id = ?
         ORDER BY wm.watched_at DESC'
    );
    $watchedStmt->execute([$userId]);
    $watchedMovies = array_map(static function (array $movie): array {
        return [
            'id' => (int) $movie['id'],
            'Naslov' => $movie['title'],
            'Zanr' => $movie['genre'],
            'Godina' => (int) $movie['year'],
            'Trajanje_min' => (int) $movie['duration_min'],
            'Ocjena' => (float) $movie['rating'],
            'Rezisery' => $movie['director'],
            'Zemlja_porijekla' => $movie['origin_country'],
            'watchedAt' => $movie['watched_at'],
        ];
    }, $watchedStmt->fetchAll());

    jsonResponse([
        'movies' => $movies,
        'watchedMovies' => $watchedMovies,
    ]);
}

if ($method === 'POST') {
    $input = readJsonInput();
    $action = (string) ($input['action'] ?? '');

    if ($action === 'confirmMarathon') {
        moveWantedMoviesToWatched($pdo, $userId);
    }

    $movieId = (int) ($input['movieId'] ?? 0);
    if ($movieId <= 0) {
        jsonResponse(['message' => 'Nedostaje ID filma.'], 400);
    }

    $movieStmt = $pdo->prepare('SELECT id, title, rating FROM movies WHERE id = ?');
    $movieStmt->execute([$movieId]);
    $movie = $movieStmt->fetch();
    if (!$movie) {
        jsonResponse(['message' => 'Film nije pronaden.'], 404);
    }

    $watchedStmt = $pdo->prepare('SELECT 1 FROM watched_movies WHERE user_id = ? AND movie_id = ?');
    $watchedStmt->execute([$userId, $movieId]);
    if ($watchedStmt->fetchColumn()) {
        jsonResponse(['message' => 'Film je vec oznacen kao pogledan.'], 409);
    }

    $insertStmt = $pdo->prepare('INSERT IGNORE INTO wanted_movies (user_id, movie_id) VALUES (?, ?)');
    $insertStmt->execute([$userId, $movieId]);

    if ($insertStmt->rowCount() === 0) {
        jsonResponse(['message' => 'Film je vec u vasoj videoteci.'], 409);
    }

    jsonResponse([
        'message' => '"' . $movie['title'] . '" je dodan u vasu videoteka listu.',
        'warning' => (float) $movie['rating'] < 5.0
            ? 'Ovaj film ima nisku ocjenu. Jeste li sigurni da ga zelite dodati?'
            : null,
    ], 201);
}

if ($method === 'DELETE') {
    $movieId = (int) ($_GET['movieId'] ?? 0);
    if ($movieId <= 0) {
        jsonResponse(['message' => 'Nedostaje ID filma.'], 400);
    }

    $stmt = $pdo->prepare('DELETE FROM wanted_movies WHERE user_id = ? AND movie_id = ?');
    $stmt->execute([$userId, $movieId]);

    if ($stmt->rowCount() === 0) {
        jsonResponse(['message' => 'Film nije u vasoj videoteci.'], 404);
    }

    jsonResponse(['message' => 'Film je uklonjen iz videoteke.']);
}

if ($method === 'PUT') {
    moveWantedMoviesToWatched($pdo, $userId);
}

jsonResponse(['message' => 'Metoda nije podrzana.'], 405);

function moveWantedMoviesToWatched(PDO $pdo, int $userId): void
{
    $pdo->beginTransaction();

    try {
        $countStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM wanted_movies
             WHERE user_id = ?'
        );
        $countStmt->execute([$userId]);
        $movieCount = (int) $countStmt->fetchColumn();

        if ($movieCount === 0) {
            $pdo->rollBack();
            jsonResponse(['message' => 'Videoteka je prazna.'], 400);
        }

        $insertStmt = $pdo->prepare(
            'INSERT IGNORE INTO watched_movies (user_id, movie_id)
             SELECT user_id, movie_id
             FROM wanted_movies
             WHERE user_id = ?'
        );
        $insertStmt->execute([$userId]);

        $deleteStmt = $pdo->prepare(
            'DELETE FROM wanted_movies
             WHERE user_id = ?'
        );
        $deleteStmt->execute([$userId]);

        $pdo->commit();

        jsonResponse([
            'message' => 'Filmovi su premjesteni u watched_movies.',
            'count' => $movieCount,
        ]);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        jsonResponse([
            'message' => 'Dogodila se greska pri spremanju pogledanih filmova.',
            'details' => $e->getMessage(),
        ], 500);
    }
}
