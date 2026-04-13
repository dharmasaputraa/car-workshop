<?php

namespace App\Http\Resources\Api\V1\User;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public $collects = UserResource::class;

    public function toResponse($request): JsonResponse
    {
        $paginator = $this->resource;

        $data = $this->collection->map(
            fn(UserResource $resource) => $resource->resolve($request)['data']
        )->values();

        return response()->json([
            'data'  => $data,
            'meta'  => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ],
        ], 200, ['Content-Type' => 'application/vnd.api+json']);
    }
}
