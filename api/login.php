<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$input = readJsonInput();

$username = normalizeUsername((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');

$stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['message' => 'Pogresno korisnicko ime ili lozinka.'], 401);
}

startAppSession();
$_SESSION['user_id'] = (int) $user['id'];

jsonResponse([
    'message' => 'Prijava je uspjesna.',
    'user' => [
        'id' => (int) $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
    ],
]);
