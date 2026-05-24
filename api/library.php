<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$userId = requireLogin();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $stmt = $pdo->prepare(
        'SELECT m.id, m.title, m.genre, m.year, m.duration_min, m.rating, m.director, m.origin_country
         FROM wanted_movies wm
         INNER JOIN movies m ON m.id = wm.movie_id
         WHERE wm.user_id = ?
         ORDER BY wm.added_at DESC'
    );
    $stmt->execute([$userId]);
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
    }, $stmt->fetchAll());

    jsonResponse(['movies' => $movies]);
}

if ($method === 'POST') {
    $input = readJsonInput();
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

    $pdo->beginTransaction();

    try {

        // Get all movies from wanted_movies
        $selectStmt = $pdo->prepare(
            'SELECT movie_id
             FROM wanted_movies
             WHERE user_id = ?'
        );

        $selectStmt->execute([$userId]);

        $movies = $selectStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!$movies) {
            $pdo->rollBack();
            jsonResponse(['message' => 'Videoteka je prazna.'], 400);
        }

        // Insert into watched_movies
        $insertStmt = $pdo->prepare(
            'INSERT IGNORE INTO watched_movies (user_id, movie_id)
             VALUES (?, ?)'
        );

        foreach ($movies as $movieId) {
            $insertStmt->execute([$userId, $movieId]);
        }

        // Remove from wanted_movies
        $deleteStmt = $pdo->prepare(
            'DELETE FROM wanted_movies
             WHERE user_id = ?'
        );

        $deleteStmt->execute([$userId]);

        $pdo->commit();

        jsonResponse([
            'message' => 'Filmovi su premjesteni u watched_movies.',
            'count' => count($movies),
        ]);

    } catch (Exception $e) {

        $pdo->rollBack();

        jsonResponse([
            'message' => 'Dogodila se greska.'
        ], 500);
    }
}

jsonResponse(['message' => 'Metoda nije podrzana.'], 405);
