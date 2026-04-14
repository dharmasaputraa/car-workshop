<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class BaseJsonApiResource extends JsonApiResource
{
    protected static function newCollection($resource)
    {
        return new BaseJsonApiCollection($resource, static::class);
    }
}
