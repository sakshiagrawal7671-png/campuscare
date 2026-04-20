<?php
declare(strict_types=1);

function jsonResponse(int $statusCode, array $payload): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function successResponse(array $data = [], string $message = 'Success', int $statusCode = 200): void
{
    jsonResponse($statusCode, [
        'status' => 'success',
        'message' => $message,
        'data' => $data,
    ]);
}

function errorResponse(string $message, int $statusCode = 400, array $errors = []): void
{
    jsonResponse($statusCode, [
        'status' => 'error',
        'message' => $message,
        'errors' => $errors,
    ]);
}

