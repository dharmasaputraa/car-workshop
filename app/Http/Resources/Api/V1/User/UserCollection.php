<?php

namespace App\Http\Resources\Api\V1\User;

use App\Http\Resources\Api\V1\BaseJsonApiCollection;

class UserCollection extends BaseJsonApiCollection
{
    public function __construct($resource)
    {
        parent::__construct($resource, UserResource::class);
    }
}
