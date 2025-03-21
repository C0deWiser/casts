<?php

namespace Tests;

use Codewiser\Rules\InstanceOfRule;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PHPUnit\Framework\TestCase;
use Stubs\Entity;
use Stubs\FooBarModel;
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

        $rules = $request->nullableMorph('commentable', [Entity::class, FooBarModel::class]);

        $expected = [
            "commentable_id"   => [
                "nullable",
                "required_with:commentable_type",
                "exists:".(new Entity)->getTable().",".(new Entity)->getKeyName()
            ],
            "commentable_type" => [
                "nullable",
                "required_with:commentable_id",
                new InstanceOfRule([Entity::class, FooBarModel::class])
            ]
        ];

        $this->assertEquals($expected, $rules);
    }

    public function testMorph()
    {
        $request = new FormRequest();

        $request->merge([
            'commentable_type' => Entity::class,
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
                new InstanceOfRule([Entity::class])
            ]
        ];

        $this->assertEquals($expected, $rules);
    }

    public function testInstanceOfRule()
    {
        $rule = new InstanceOfRule(Entity::class);

        $fail = fn() => $this->fail();
        $rule->validate('commentable_type', 'entity', $fail);
        $this->assertTrue(true);

        $fail = fn() => $this->fail();
        $rule->validate('commentable_type', Entity::class, $fail);
        $this->assertTrue(true);

        $rule = new InstanceOfRule([Model::class, FooBarModel::class]);

        $fail = fn() => $this->assertTrue(true);
        $rule->validate('commentable_type', 'entity', $fail);
    }

    public function testInstanceOfRuleFiled()
    {
        $rule = new InstanceOfRule(FooBarModel::class);

        // It means: the fail closure was called
        $this->expectException(BindingResolutionException::class);
        $fail = fn() => $this->assertTrue(true);
        $rule->validate('commentable_type', 'entity', $fail);
    }
}
