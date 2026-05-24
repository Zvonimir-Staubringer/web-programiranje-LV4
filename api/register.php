<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
$input = readJsonInput();

$username = normalizeUsername((string) ($input['username'] ?? ''));
$password = (string) ($input['password'] ?? '');
$confirmPassword = (string) ($input['confirmPassword'] ?? '');

if ($username === '' || mb_strlen($username) < 3) {
    jsonResponse(['message' => 'Username mora imati barem 3 znaka.'], 400);
}

if (mb_strlen($password) < 6) {
    jsonResponse(['message' => 'Lozinka mora imati barem 6 znakova.'], 400);
}

if ($password !== $confirmPassword) {
    jsonResponse(['message' => 'Lozinke se ne podudaraju.'], 400);
}

$existingStmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$existingStmt->execute([$username]);
if ($existingStmt->fetch()) {
    jsonResponse(['message' => 'Korisnicko ime je zauzeto.'], 409);
}

$insertStmt = $pdo->prepare('INSERT INTO users (username, password_hash, role) VALUES (?, ?, "user")');
$insertStmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);

startAppSession();
$_SESSION['user_id'] = (int) $pdo->lastInsertId();

jsonResponse([
    'message' => 'Registracija je uspjesna.',
    'user' => [
        'id' => (int) $_SESSION['user_id'],
        'username' => $username,
        'role' => 'user',
    ],
], 201);
