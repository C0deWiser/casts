<?php

namespace Stubs;

use Codewiser\Casts\AsObject;
use Codewiser\Casts\AsStruct;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @property null|FooBarStruct $nullable
 * @property FooBarStruct $required
 * @property Collection $array_collection
 * @property Collection $object_collection
 * @property Collection $custom_array_collection
 * @property Collection $custom_object_collection
 * @property FooBar $object
 */
class FooBarModel extends Model
{
    protected function casts(): array
    {
        return [
            'nullable' => AsStruct::using(FooBarStruct::class)->nullable(),
            'required' => AsStruct::using(FooBarStruct::class)->required(),

            'array_collection' => AsCollection::class,
            'object_collection' => AsCollection::of(FooBar::class),
            'custom_array_collection' => AsCollection::using(FooBarCollection::class),
            'custom_object_collection' => AsCollection::using(FooBarCollection::class, FooBar::class),
            'object' => AsObject::of(FooBar::class),
        ];
    }
}