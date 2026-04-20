<?php
declare(strict_types=1);

function requireMethod(string $expectedMethod): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($expectedMethod)) {
        errorResponse('Method not allowed.', 405);
    }
}

function getJsonInput(): array
{
    $rawInput = file_get_contents('php://input');

    if ($rawInput === false || trim($rawInput) === '') {
        return [];
    }

    $decoded = json_decode($rawInput, true);

    if (!is_array($decoded)) {
        errorResponse('Invalid JSON payload.', 400);
    }

    return $decoded;
}

function requireFields(array $input, array $fields): void
{
    $missingFields = [];

    foreach ($fields as $field) {
        if (!array_key_exists($field, $input) || $input[$field] === '' || $input[$field] === null) {
            $missingFields[] = $field;
        }
    }

    if ($missingFields !== []) {
        errorResponse('Missing required fields.', 422, [
            'missing' => $missingFields,
        ]);
    }
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';

    if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
        return trim($matches[1]);
    }

    return null;
}

