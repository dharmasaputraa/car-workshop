<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\JsonApi\AnonymousResourceCollection;
use Illuminate\Http\Resources\JsonApi\JsonApiRequest;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;
use Illuminate\Support\Arr;

class BaseJsonApiCollection extends AnonymousResourceCollection
{
    public function __construct($resource, string $collects)
    {
        parent::__construct($resource, $collects);
    }

    public function toResponse($request)
    {
        $jsonApiRequest = $request instanceof JsonApiRequest
            ? $request
            : JsonApiRequest::createFrom($request);

        return parent::toResponse($jsonApiRequest);
    }

    public function with($request): array
    {
        $jsonApiRequest = $request instanceof JsonApiRequest
            ? $request
            : JsonApiRequest::createFrom($request);

        $seen = [];

        $included = $this->collection
            ->flatMap(function ($resource) use ($jsonApiRequest) {
                /** @var JsonApiResource $resource */
                return $resource->resolveIncludedResourceObjects($jsonApiRequest)->all();
            })
            ->filter(function (array $item) use (&$seen) {
                $key = $item['id'] . ':' . $item['type'];
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            })
            ->map(fn(array $item) => Arr::except($item, ['_uniqueKey']))
            ->values()
            ->all();

        return array_filter(['included' => $included]);
    }
}
