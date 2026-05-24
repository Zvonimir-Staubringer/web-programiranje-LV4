<?php

declare(strict_types=1);

function normalizeUsername(string $value): string
{
    return mb_strtolower(trim($value));
}

function slugify(string $value): string
{
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function nextImageUploadPath(string $title): array
{
    $base = slugify($title);
    if ($base === '') {
        $base = 'image';
    }

    $fileName = $base . '-' . time();
    return [
        'dir' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'uploads',
        'fileName' => $fileName,
    ];
}

function parseCsvLine(string $line): array
{
    $values = str_getcsv($line);
    return array_map(static fn ($value) => trim((string) $value), $values);
}

function stripUtf8Bom(string $value): string
{
    return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
}
