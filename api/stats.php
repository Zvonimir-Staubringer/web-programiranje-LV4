<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$stmt = $pdo->query('SELECT genre, rating FROM movies');
$genreCount = [];
$ratingSums = [];
$ratingCounts = [];

foreach ($stmt->fetchAll() as $movie) {
    $genres = array_filter(array_map('trim', explode(',', (string) $movie['genre'])));
    foreach ($genres as $genre) {
        $genreCount[$genre] = ($genreCount[$genre] ?? 0) + 1;
        $ratingSums[$genre] = ($ratingSums[$genre] ?? 0.0) + (float) $movie['rating'];
        $ratingCounts[$genre] = ($ratingCounts[$genre] ?? 0) + 1;
    }
}

ksort($genreCount);
ksort($ratingSums);

$genreDistribution = [];
foreach ($genreCount as $genre => $count) {
    $genreDistribution[] = ['genre' => $genre, 'count' => $count];
}

$averageRatings = [];
foreach ($ratingSums as $genre => $sum) {
    $averageRatings[] = [
        'genre' => $genre,
        'average' => round($sum / $ratingCounts[$genre], 2),
    ];
}

jsonResponse([
    'genreDistribution' => $genreDistribution,
    'averageRatings' => $averageRatings,
]);
