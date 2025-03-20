<?php

namespace Tests;

use Codewiser\Rules\InstanceOfRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;
use Stubs\Entity;
use Stubs\FormRequest;

class MorphTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Relation::enforceMorphMap([
            'entity' => Entity::class
        ]);
    }

    public function testNullableMorph()
    {
        $request = new FormRequest();

        $request->merge([
            'commentable_type' => 'entity',
            'commentable_id'   => 1
        ]);

        $rules = $request->nullableMorph('commentable', [Entity::class]);

        $expected = [
            "commentable_id"   => [
                "nullable",
                "required_with:commentable_type",
                "exists:".(new Entity)->getTable().",".(new Entity)->getKeyName()
            ],
            "commentable_type" => [
                "nullable",
                "required_with:commentable_id",
                new InstanceOfRule([Model::class, Entity::class])
            ]
        ];

        $this->assertEquals($expected, $rules);
    }

    public function testMorph()
    {
        $request = new FormRequest();

        $request->merge([
            'commentable_type' => 'entity',
            'commentable_id'   => 1
        ]);

        $rules = $request->morph('commentable', Entity::class);

        $expected = [
            "commentable_id"   => [
                "required",
                "exists:".(new Entity)->getTable().",".(new Entity)->getKeyName()
            ],
            "commentable_type" => [
                "required",
                new InstanceOfRule([Model::class, Entity::class])
            ]
        ];

        $this->assertEquals($expected, $rules);
    }
}
