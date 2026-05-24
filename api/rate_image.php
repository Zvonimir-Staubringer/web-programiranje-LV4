<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$userId = requireLogin();
$input = readJsonInput();

$imageId = (int) ($input['imageId'] ?? 0);
$rating = (int) ($input['rating'] ?? 0);

if ($imageId <= 0) {
    jsonResponse(['message' => 'Nedostaje ID slike.'], 400);
}

if ($rating < 1 || $rating > 5) {
    jsonResponse(['message' => 'Ocjena mora biti izmedu 1 i 5.'], 400);
}

$imageStmt = $pdo->prepare('SELECT title FROM images WHERE id = ?');
$imageStmt->execute([$imageId]);
$title = $imageStmt->fetchColumn();

if (!$title) {
    jsonResponse(['message' => 'Slika nije pronadena.'], 404);
}

$upsertStmt = $pdo->prepare(
    'INSERT INTO ratings (user_id, image_id, rating)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE rating = VALUES(rating), updated_at = CURRENT_TIMESTAMP'
);
$upsertStmt->execute([$userId, $imageId, $rating]);

$statsStmt = $pdo->prepare('SELECT AVG(rating) AS average_rating, COUNT(*) AS rating_count FROM ratings WHERE image_id = ?');
$statsStmt->execute([$imageId]);
$stats = $statsStmt->fetch();

jsonResponse([
    'message' => 'Ocjena za "' . $title . '" je spremljena.',
    'averageRating' => round((float) $stats['average_rating'], 2),
    'ratingCount' => (int) $stats['rating_count'],
]);
