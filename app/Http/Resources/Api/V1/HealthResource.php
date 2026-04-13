<?php

namespace App\Http\Resources\Api\V1;

use App\DTOs\Health\HealthData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class HealthResource extends JsonApiResource
{
    public function __construct(private readonly HealthData $data)
    {
        parent::__construct($data);
    }

    public function toId(Request $request): string
    {
        return 'health';
    }

    public function toType(Request $request): string
    {
        return 'health';
    }

    public function toAttributes(Request $request): array
    {
        return [
            'healthy'  => $this->data->healthy,
            'message'  => $this->data->message,
            'details'  => $this->data->details,
        ];
    }
}
