<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\BaseJsonApiCollection;
use App\Http\Resources\Api\V1\Service\ServiceResource;

class ServiceCollection extends BaseJsonApiCollection
{
    public function __construct($resource)
    {
        parent::__construct($resource, ServiceResource::class);
    }
}
