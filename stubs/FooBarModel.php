<?php

namespace Stubs;

use Codewiser\Casts\AsStruct;
use Illuminate\Database\Eloquent\Model;

/**
 * @property null|FooBarStruct $nullable
 * @property FooBarStruct $required
 */
class FooBarModel extends Model
{
    protected function casts(): array
    {
        return [
            'nullable' => AsStruct::using(FooBarStruct::class)->nullable(),
            'required' => AsStruct::using(FooBarStruct::class)->required(),
        ];
    }
}