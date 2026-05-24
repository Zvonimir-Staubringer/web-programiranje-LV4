<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method !== 'GET') {
    jsonResponse(['message' => 'Metoda nije podrzana.'], 405);
}

$userId = currentUserId();
$sql = '
    SELECT i.id, i.file_path, i.title, i.description, i.source, i.created_at,
           AVG(r.rating) AS average_rating,
           COUNT(r.id) AS rating_count';

if ($userId !== null) {
    $sql .= ',
           (SELECT r2.rating FROM ratings r2 WHERE r2.user_id = :user_id AND r2.image_id = i.id LIMIT 1) AS user_rating';
} else {
    $sql .= ', NULL AS user_rating';
}

$sql .= '
    FROM images i
    LEFT JOIN ratings r ON r.image_id = i.id
    GROUP BY i.id, i.file_path, i.title, i.description, i.source, i.created_at
    ORDER BY i.created_at DESC, i.id DESC';

$stmt = $pdo->prepare($sql);
if ($userId !== null) {
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
}
$stmt->execute();

$images = array_map(static function (array $image): array {
    return [
        'id' => (int) $image['id'],
        'file' => $image['file_path'],
        'title' => $image['title'],
        'description' => $image['description'],
        'source' => $image['source'],
        'averageRating' => $image['average_rating'] === null ? null : round((float) $image['average_rating'], 2),
        'ratingCount' => (int) $image['rating_count'],
        'userRating' => $image['user_rating'] === null ? null : (int) $image['user_rating'],
    ];
}, $stmt->fetchAll());

jsonResponse(['images' => $images]);
