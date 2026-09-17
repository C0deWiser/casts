<?php

namespace Stubs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Role $resource
 */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name'  => $this->resource->name,
            'value' => $this->resource->value,
        ];
    }
}