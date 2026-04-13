<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\JsonResponse;
use Throwable;

class ErrorResource
{
    public static function make(
        string $title,
        string $detail,
        int $status = 400,
        ?string $code = null,
        ?Throwable $exception = null,
    ): JsonResponse {
        $error = [
            'status' => (string) $status,
            'title'  => $title,
            'detail' => $detail,
        ];

        if ($code) {
            $error['code'] = $code;
        }

        if ($exception && app()->hasDebugModeEnabled()) {
            $error['meta']['exception'] = [
                'class'   => get_class($exception),
                'message' => $exception->getMessage(),
                'trace'   => collect($exception->getTrace())->take(10)->toArray(),
            ];
        }

        return response()->json(
            ['errors' => [$error]],
            $status,
            ['Content-Type' => 'application/vnd.api+json']
        );
    }

    public static function validation(array $errors): JsonResponse
    {
        $formatted = collect($errors)
            ->flatMap(fn($messages, $field) => collect($messages)->map(fn($message) => [
                'status' => '422',
                'title'  => 'Validation Error',
                'detail' => $message,
                'source' => ['pointer' => '/data/attributes/' . str_replace('.', '/', $field)],
            ]))
            ->values()
            ->all();

        return response()->json(
            ['errors' => $formatted],
            422,
            ['Content-Type' => 'application/vnd.api+json']
        );
    }

    public static function notFound(string $detail = 'Resource not found.'): JsonResponse
    {
        return self::make('Not Found', $detail, 404, 'NOT_FOUND');
    }

    public static function forbidden(string $detail = 'This action is unauthorized.'): JsonResponse
    {
        return self::make('Forbidden', $detail, 403, 'FORBIDDEN');
    }
}
