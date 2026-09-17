<?php

namespace Tests;

use Codewiser\Collections\EnumCollection as EnumCollection;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\TestCase;
use Stubs\FooBar;
use Stubs\FooBarCollection;
use Stubs\Role;

class CastTest extends TestCase
{
    public function test_collection_cast_using_object()
    {
        $cast = AsCollection::castUsing([FooBarCollection::class, FooBar::class]);

        $payload = [
            'name'      => 'option-1',
            'value'     => 'five',
            'is_locked' => true,
        ];
        $object = new FooBar($payload);
        $states = json_encode([$payload]);

        $get = $cast->get(new User(), 'states', $states, ['states' => $states]);
        $this->assertInstanceOf(FooBarCollection::class, $get);
        $this->assertCount(1, $get);
        $this->assertInstanceOf(FooBar::class, $get->first());
        $this->assertSame($object->toArray(), $get->first()->toArray());

        $set = $cast->set(new User(), 'states', FooBarCollection::make([new FooBar($payload)]), []);
        $this->assertSame(['states' => $states], $set);
    }

    public function test_collection_cast_using_enum()
    {
        $cast = AsCollection::castUsing([EnumCollection::class, Role::class]);

        $roles = json_encode(['admin']);

        $get = $cast->get(new User(), 'roles', $roles, ['roles' => $roles]);
        $this->assertInstanceOf(EnumCollection::class, $get);
        $this->assertCount(1, $get);
        $this->assertInstanceOf(Role::class, $get->first());
        $this->assertSame(Role::Admin, $get->first());

        $set = $cast->set(new User(), 'roles', EnumCollection::make([Role::Admin]), []);
        $this->assertSame(['roles' => $roles], $set);
    }
}