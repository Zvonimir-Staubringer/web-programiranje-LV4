<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$userId = requireLogin();

$stmt = $pdo->prepare(
    'SELECT i.id AS image_id, i.title, i.file_path, r.rating, r.updated_at
     FROM ratings r
     INNER JOIN images i ON i.id = r.image_id
     WHERE r.user_id = ?
     ORDER BY r.updated_at DESC'
);
$stmt->execute([$userId]);

$ratings = array_map(static function (array $row): array {
    return [
        'imageId' => (int) $row['image_id'],
        'rating' => (int) $row['rating'],
        'updatedAt' => $row['updated_at'],
        'image' => [
            'id' => (int) $row['image_id'],
            'title' => $row['title'],
            'file' => $row['file_path'],
        ],
    ];
}, $stmt->fetchAll());

jsonResponse(['ratings' => $ratings]);
