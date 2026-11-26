<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ApiResponder
{
    protected function success(mixed $data = null, string $message = 'Permintaan berhasil diproses.', int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'errors' => null,
            'meta' => ['request_id' => (string) request()->headers->get('X-Request-ID', '')],
        ], $status);
    }
}
