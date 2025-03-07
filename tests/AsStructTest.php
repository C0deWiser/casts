<?php

namespace Tests;

use Codewiser\Casts\AsStruct;
use Codewiser\Casts\AsStructCollection;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;
use Stubs\FooBarCollection;
use Stubs\FooBarModel;
use Stubs\FooBarStruct;

class AsStructTest extends TestCase
{
    public function testAsStructStrings()
    {
        $this->assertEquals(
            AsStruct::class.':'.FooBarStruct::class,
            AsStruct::using(FooBarStruct::class)->nullable()
        );

        $this->assertEquals(
            AsStruct::class.':'.FooBarStruct::class.',required',
            AsStruct::using(FooBarStruct::class)->required()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class,
            AsStructCollection::using(FooBarStruct::class)->nullable()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class,
            AsStruct::collects(FooBarStruct::class)->nullable()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class,
            AsStructCollection::using(FooBarStruct::class, FooBarCollection::class)->nullable()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class,
            AsStructCollection::using(FooBarStruct::class)->with(FooBarCollection::class)->nullable()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class,
            AsStruct::collects(FooBarStruct::class)->with(FooBarCollection::class)->nullable()
        );



        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class,
            AsStruct::collects(FooBarStruct::class)
                ->with(Collection::class)
                ->with(FooBarCollection::class)
                ->nullable()
        );



        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.',required',
            AsStructCollection::using(FooBarStruct::class)->required()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.',required',
            AsStruct::collects(FooBarStruct::class)->required()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class.',required',
            AsStructCollection::using(FooBarStruct::class, FooBarCollection::class)->required()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class.',required',
            AsStructCollection::using(FooBarStruct::class)->with(FooBarCollection::class)->required()
        );

        $this->assertEquals(
            AsStructCollection::class.':'.FooBarStruct::class.','.FooBarCollection::class.',required',
            AsStruct::collects(FooBarStruct::class)->with(FooBarCollection::class)->required()
        );
    }

    public function testAsStruct()
    {
        $model = new FooBarModel;

        $model->required->test = 'good';

        $this->assertEquals(['required'=> ['test'=>'good']], $model->toArray());
    }
}
