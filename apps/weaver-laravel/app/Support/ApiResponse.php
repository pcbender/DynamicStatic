<?php
namespace App\Support;

use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function ok(array $data = [], int $status = 200): JsonResponse
    {
        return response()->json(['status' => 'ok', 'data' => $data], $status);
    }

    public static function error(string $message, int $status = 400, array $meta = []): JsonResponse
    {
        return response()->json(['status' => 'error', 'message' => $message, 'meta' => $meta], $status);
    }
}
