<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

$pdo = getPdo();
requireAdmin($pdo);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $input = readJsonInput();
    $title = trim((string) ($input['title'] ?? ''));
    $description = trim((string) ($input['description'] ?? ''));
    $dataUrl = (string) ($input['dataUrl'] ?? '');

    if ($title === '') {
        jsonResponse(['message' => 'Naziv slike je obavezan.'], 400);
    }

    if (!preg_match('/^data:(image\/(?:png|jpeg));base64,(.+)$/', $dataUrl, $matches)) {
        jsonResponse(['message' => 'Slika mora biti JPEG ili PNG format.'], 400);
    }

    $mimeType = $matches[1];
    $binary = base64_decode($matches[2], true);
    if ($binary === false || strlen($binary) === 0) {
        jsonResponse(['message' => 'Datoteku nije moguce procitati.'], 400);
    }

    if (strlen($binary) > 5 * 1024 * 1024) {
        jsonResponse(['message' => 'Slika mora biti manja od 5 MB.'], 400);
    }

    $pathInfo = nextImageUploadPath($title);
    if (!is_dir($pathInfo['dir']) && !mkdir($pathInfo['dir'], 0777, true) && !is_dir($pathInfo['dir'])) {
        jsonResponse(['message' => 'Nije moguce kreirati upload direktorij.'], 500);
    }

    $extension = $mimeType === 'image/png' ? '.png' : '.jpg';
    $relativePath = 'public/images/uploads/' . $pathInfo['fileName'] . $extension;
    $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

    if (file_put_contents($absolutePath, $binary) === false) {
        jsonResponse(['message' => 'Nije moguce spremiti sliku.'], 500);
    }

    $stmt = $pdo->prepare("INSERT INTO images (file_path, title, description, source) VALUES (?, ?, ?, 'upload')");
    $stmt->execute([$relativePath, $title, $description]);

    jsonResponse(['message' => 'Slika je dodana.'], 201);
}

if ($method === 'DELETE') {
    $imageId = (int) ($_GET['id'] ?? 0);
    if ($imageId <= 0) {
        jsonResponse(['message' => 'Nedostaje ID slike.'], 400);
    }

    $stmt = $pdo->prepare('SELECT file_path, source FROM images WHERE id = ?');
    $stmt->execute([$imageId]);
    $image = $stmt->fetch();
    if (!$image) {
        jsonResponse(['message' => 'Slika nije pronadena.'], 404);
    }

    $deleteStmt = $pdo->prepare('DELETE FROM images WHERE id = ?');
    $deleteStmt->execute([$imageId]);

    if ($image['source'] === 'upload') {
        $absolutePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $image['file_path']);
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }
    }

    jsonResponse(['message' => 'Slika je obrisana.']);
}

jsonResponse(['message' => 'Metoda nije podrzana.'], 405);
