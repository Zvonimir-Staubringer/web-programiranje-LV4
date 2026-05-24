<?php

declare(strict_types=1);

require_once __DIR__ . '/response.php';
require_once __DIR__ . '/db.php';

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function currentUserId(): ?int
{
    startAppSession();
    $userId = $_SESSION['user_id'] ?? null;
    return is_int($userId) ? $userId : (is_numeric($userId) ? (int) $userId : null);
}

function requireLogin(): int
{
    $userId = currentUserId();
    if ($userId === null) {
        jsonResponse(['message' => 'Za ovu akciju potrebna je prijava.'], 401);
    }

    return $userId;
}

function requireAdmin(PDO $pdo): int
{
    $userId = requireLogin();
    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $role = $stmt->fetchColumn();

    if ($role !== 'admin') {
        jsonResponse(['message' => 'Samo admin moze koristiti ovu akciju.'], 403);
    }

    return $userId;
}
