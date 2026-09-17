<?php

namespace Tests;

use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Stubs\FooBar;
use Stubs\FooBarCollection;
use Stubs\FooBarModel;

class CastTest extends TestCase
{
    public function testObjectCasting()
    {
        $model = new FooBarModel();
        $this->assertNull($model->object);

        $data = [
            'name' => 'option-1',
            'value' => 'foo',
            'is_locked' => false,
        ];

        // Set as array
        $model->object = $data;
        $this->assertInstanceOf(FooBar::class, $model->object);
        $this->assertEquals($data, $model->object->toArray());
        $this->assertTrue($model->isDirty());

        $object = new FooBar($data);

        // Set as object
        $model = new FooBarModel();
        $model->object = $object;
        $this->assertInstanceOf(FooBar::class, $model->object);
        $this->assertEquals($object, $model->object);
        $this->assertTrue($model->isDirty());

        // Reset
        $model->object = null;
        $this->assertNull($model->object);
    }

    public function testArrayCollection()
    {
        $model = new FooBarModel();
        $this->assertNull($model->array_collection);

        $data = [
            'name' => 'option-1',
            'value' => 'foo',
            'is_locked' => false,
        ];

        // Set as array
        $model->array_collection = [$data];
        $this->assertInstanceOf(Collection::class, $model->array_collection);
        $this->assertEquals([$data], $model->array_collection->toArray());
        $this->assertTrue($model->isDirty());

        // Set as Collection
        $model = new FooBarModel();
        $model->array_collection = collect([$data]);
        $this->assertInstanceOf(Collection::class, $model->array_collection);
        $this->assertEquals([$data], $model->array_collection->toArray());
        $this->assertTrue($model->isDirty());

        // Reset
        $model->array_collection = null;
        $this->assertNull($model->array_collection);
    }

    public function testArrayCustomCollection()
    {
        $model = new FooBarModel();
        $this->assertNull($model->custom_array_collection);

        $data = [
            'name' => 'option-1',
            'value' => 'foo',
            'is_locked' => false,
        ];

        // Set as array
        $model->custom_array_collection = [$data];
        $this->assertInstanceOf(FooBarCollection::class, $model->custom_array_collection);
        $this->assertEquals([$data], $model->custom_array_collection->toArray());
        $this->assertTrue($model->isDirty());

        // Set as Collection (it keeps original collection)
        $model = new FooBarModel();
        $model->custom_array_collection = collect([$data]);
        $this->assertInstanceOf(Collection::class, $model->custom_array_collection);
        $this->assertEquals([$data], $model->custom_array_collection->toArray());
        $this->assertTrue($model->isDirty());

        // Reset
        $model->custom_array_collection = null;
        $this->assertNull($model->custom_array_collection);
    }

    public function testObjectCollection()
    {
        $model = new FooBarModel();
        $this->assertNull($model->object_collection);

        $data = [
            'name' => 'option-1',
            'value' => 'foo',
            'is_locked' => false,
        ];

        // Set as array
        $model->object_collection = [$data];
        $this->assertInstanceOf(Collection::class, $model->object_collection);
        $this->assertInstanceOf(FooBar::class, $model->object_collection->first());
        $this->assertEquals([$data], $model->object_collection->toArray());
        $this->assertTrue($model->isDirty());

        // Set as Collection (doesn't map array to object)
        $model = new FooBarModel();
        $model->object_collection = collect([$data]);
        $this->assertInstanceOf(Collection::class, $model->object_collection);
        $this->assertIsArray($model->object_collection->first());
        $this->assertEquals([$data], $model->object_collection->toArray());
        $this->assertTrue($model->isDirty());

        $object = new FooBar($data);

        // Set as array of objects
        $model = new FooBarModel();
        $model->object_collection = [$object];
        $this->assertInstanceOf(Collection::class, $model->object_collection);
        $this->assertInstanceOf(FooBar::class, $model->object_collection->first());
        $this->assertEquals($object, $model->object_collection->first());
        $this->assertTrue($model->isDirty());

        // Set as Collection of objects
        $model = new FooBarModel();
        $model->object_collection = collect([$object]);
        $this->assertInstanceOf(Collection::class, $model->object_collection);
        $this->assertInstanceOf(FooBar::class, $model->object_collection->first());
        $this->assertEquals($object, $model->object_collection->first());
        $this->assertTrue($model->isDirty());

        // Reset
        $model->object_collection = null;
        $this->assertNull($model->object_collection);
    }

    public function testObjectCustomCollection()
    {
        $model = new FooBarModel();
        $this->assertNull($model->custom_object_collection);

        $data = [
            'name' => 'option-1',
            'value' => 'foo',
            'is_locked' => false,
        ];

        // Set as array
        $model->custom_object_collection = [$data];
        $this->assertInstanceOf(FooBarCollection::class, $model->custom_object_collection);
        $this->assertInstanceOf(FooBar::class, $model->custom_object_collection->first());
        $this->assertEquals([$data], $model->custom_object_collection->toArray());
        $this->assertTrue($model->isDirty());

        // Set as Collection (it keeps original collection and doesn't map array to object)
        $model = new FooBarModel();
        $model->custom_object_collection = collect([$data]);
        $this->assertInstanceOf(Collection::class, $model->custom_object_collection);
        $this->assertIsArray($model->custom_object_collection->first());
        $this->assertEquals([$data], $model->custom_object_collection->toArray());
        $this->assertTrue($model->isDirty());

        $object = new FooBar($data);

        // Set as array of objects
        $model = new FooBarModel();
        $model->custom_object_collection = [$object];
        $this->assertInstanceOf(FooBarCollection::class, $model->custom_object_collection);
        $this->assertInstanceOf(FooBar::class, $model->custom_object_collection->first());
        $this->assertEquals($object, $model->custom_object_collection->first());
        $this->assertTrue($model->isDirty());

        // Set as Collection of objects
        $model = new FooBarModel();
        $model->custom_object_collection = collect([$object]);
        $this->assertInstanceOf(Collection::class, $model->custom_object_collection);
        $this->assertInstanceOf(FooBar::class, $model->custom_object_collection->first());
        $this->assertEquals($object, $model->custom_object_collection->first());
        $this->assertTrue($model->isDirty());

        // Reset
        $model->custom_object_collection = null;
        $this->assertNull($model->custom_object_collection);
    }
}