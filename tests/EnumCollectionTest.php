<?php

namespace Tests;

use ArrayIterator;
use CachingIterator;
use Codewiser\Collections\EnumCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\LazyCollection;
use LogicException;
use PHPUnit\Framework\TestCase;
use Stubs\MapInto;
use Stubs\Rating;
use Stubs\Role;
use Stubs\RoleResource;
use Stubs\State;
use Stubs\Status;

class EnumCollectionTest extends TestCase
{
    // ─── intersect ──────────────────────────────────────────────────

    public function testIntersectReturnsCommonItems(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->intersect([Role::Admin]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Admin));

        $result = $collection->intersect(['admin']);

        $this->assertCount(0, $result);
    }

    public function testIntersectWithMultipleCommonItems(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->intersect([Role::Admin, Role::Guest]);

        $this->assertCount(2, $result);
        $this->assertTrue($result->has(Role::Admin));
        $this->assertTrue($result->has(Role::Guest));
    }

    public function testIntersectWithNoCommonItems(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->intersect([Role::Guest]);

        $this->assertTrue($result->isEmpty());
    }

    public function testIntersectWithEmptyArray(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->intersect([]);

        $this->assertTrue($result->isEmpty());
    }

    public function testIntersectEmptyCollectionWithItems(): void
    {
        $collection = new EnumCollection;

        $result = $collection->intersect([Role::Admin]);

        $this->assertTrue($result->isEmpty());
    }

    public function testIntersectReturnsStaticType(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->intersect([Role::Admin]);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }

    // ─── diff ───────────────────────────────────────────────────────

    public function testDiffReturnsItemsNotInGiven(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->diff([Role::Admin]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Guest));
        $this->assertFalse($result->has(Role::Admin));
    }

    public function testDiffWithNoOverlappingItems(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->diff([Role::Guest]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Admin));
    }

    public function testDiffWithAllItemsRemoved(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->diff([Role::Admin, Role::Guest]);

        $this->assertTrue($result->isEmpty());
    }

    public function testDiffWithEmptyArray(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->diff([]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Admin));
    }

    public function testDiffEmptyCollection(): void
    {
        $collection = new EnumCollection;

        $result = $collection->diff([Role::Admin]);

        $this->assertTrue($result->isEmpty());
    }

    public function testDiffReturnsStaticType(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->diff([]);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }

    // ─── merge ──────────────────────────────────────────────────────

    public function testMergeAddsNewItems(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->merge([Role::Guest]);

        $this->assertCount(2, $result);
        $this->assertTrue($result->has(Role::Admin));
        $this->assertTrue($result->has(Role::Guest));
    }

    public function testMergeOverwritesExistingByEnumName(): void
    {
        $collection = new EnumCollection([Status::Active, Status::Inactive]);

        $result = $collection->merge([Status::Pending, Status::Active]);

        $this->assertCount(3, $result);
        $this->assertSame([Status::Active, Status::Inactive, Status::Pending], $result->all());
    }

    public function testMergeWithEmptyArray(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->merge([]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Admin));
    }

    public function testMergeEmptyCollectionWithItems(): void
    {
        $collection = new EnumCollection;

        $result = $collection->merge([Role::Admin, Role::Guest]);

        $this->assertCount(2, $result);
        $this->assertTrue($result->has(Role::Admin));
        $this->assertTrue($result->has(Role::Guest));
    }

    public function testMergeBothEmpty(): void
    {
        $collection = new EnumCollection;

        $result = $collection->merge([]);

        $this->assertTrue($result->isEmpty());
    }

    public function testMergeReturnsStaticType(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->merge([]);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }

    public function testMergeResultContainsEnumValues(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->merge([Role::Guest]);

        $values = $result->values()->all();
        $this->assertContains(Role::Admin, $values);
        $this->assertContains(Role::Guest, $values);
    }

    // ─── has ────────────────────────────────────────────────────────

    public function testHasReturnsTrueForExistingItem(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->has(Role::Admin));
    }

    public function testHasReturnsFalseForMissingItem(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertFalse($collection->has(Role::Guest));
    }

    public function testHasWithArray(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->has([Role::Admin, Role::Guest]));
    }

    public function testHasWithArrayReturnsFalseIfAnyMissing(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertFalse($collection->has([Role::Admin, Role::Guest]));
    }

    public function testHasOnEmptyCollection(): void
    {
        $collection = new EnumCollection;

        $this->assertFalse($collection->has(Role::Admin));
    }

    // ─── doesntHave ─────────────────────────────────────────────────

    public function testDoesntHaveReturnsTrueForMissingItem(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertTrue($collection->doesntHave(Role::Guest));
    }

    public function testDoesntHaveReturnsFalseForExistingItem(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertFalse($collection->doesntHave(Role::Admin));
    }

    public function testDoesntHaveWithArray(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertTrue($collection->doesntHave([Role::Guest, Status::Active]));
    }

    public function testDoesntHaveWithArrayReturnsFalseIfAnyExists(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertFalse($collection->doesntHave([Role::Admin, Role::Guest]));
    }

    public function testDoesntHaveOnEmptyCollection(): void
    {
        $collection = new EnumCollection;

        $this->assertTrue($collection->doesntHave(Role::Admin));
    }

    // ─── hasAny ─────────────────────────────────────────────────────

    public function testHasAnyReturnsTrueIfSomeExist(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->hasAny([Role::Admin, Status::Active]));
    }

    public function testHasAnyReturnsFalseIfNoneExist(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertFalse($collection->hasAny([Role::Guest, Status::Active]));
    }

    public function testHasAnyReturnsFalseOnEmptyCollection(): void
    {
        $collection = new EnumCollection;

        $this->assertFalse($collection->hasAny([Role::Admin]));
    }

    public function testHasAnyReturnsTrueWhenAllExist(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->hasAny([Role::Admin, Role::Guest]));
    }

    // ─── forget ─────────────────────────────────────────────────────

    public function testForgetRemovesSingleItem(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->forget(Role::Guest);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Admin));
        $this->assertFalse($result->has(Role::Guest));
    }

    public function testForgetRemovesMultipleItemsViaArray(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $result = $collection->forget([Role::Admin, Status::Active]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Guest));
    }

    public function testForgetReturnsStaticType(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->forget(Role::Admin);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }

    public function testForgetReturnsSameInstance(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->forget(Role::Guest);

        $this->assertSame($collection, $result);
        $this->assertCount(1, $collection);
    }

    public function testForgetNonExistingItem(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->forget(Role::Guest);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(Role::Admin));
    }

    public function testForgetAllItems(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->forget([Role::Admin, Role::Guest]);

        $this->assertTrue($result->isEmpty());
    }

    // ─── toResourceCollection ───────────────────────────────────────

    public function testToResourceCollection(): void
    {
        $resourceCollection = (new EnumCollection([Role::Admin, Role::Guest]))
            ->toResourceCollection(RoleResource::class);

        $this->assertInstanceOf(ResourceCollection::class, $resourceCollection);
        $this->assertInstanceOf(AnonymousResourceCollection::class, $resourceCollection);
        $this->assertContainsOnlyInstancesOf(RoleResource::class, $resourceCollection->collection);

        $this->assertEquals(
            ['name' => 'Admin', 'value' => 'admin'],
            $resourceCollection->collection->first()->toArray(Request::create('/'))
        );
    }

    public function testToResourceCollectionOfEmptyCollection(): void
    {
        $resourceCollection = (new EnumCollection)->toResourceCollection();

        $this->assertInstanceOf(ResourceCollection::class, $resourceCollection);
        $this->assertTrue($resourceCollection->collection->isEmpty());
    }

    public function testToResourceCollectionCannotGuessWithoutResourceClass(): void
    {
        $this->expectException(LogicException::class);

        (new EnumCollection([Role::Admin]))->toResourceCollection();
    }

    // ══════════════════ Inherited Collection methods ══════════════════

    // ─── static constructors ─────────────────────────────────────────

    public function testConstructWithEnumArray(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertCount(2, $collection);
    }

    public function testConstructWithNull(): void
    {
        $collection = new EnumCollection(null);

        $this->assertTrue($collection->isEmpty());
    }

    public function testConstructWithCollection(): void
    {
        $collection = new EnumCollection(collect([Role::Admin]));

        $this->assertSame([Role::Admin], $collection->all());
    }

    public function testMake(): void
    {
        $collection = EnumCollection::make([Role::Admin, Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testWrapOfEnumArray(): void
    {
        $collection = EnumCollection::wrap([Role::Admin]);

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertSame([Role::Admin], $collection->all());
    }

    public function testWrapOfSingleEnum(): void
    {
        $collection = EnumCollection::wrap(Role::Admin);

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertSame([Role::Admin], $collection->all());
    }

    public function testUnwrapOfEnumCollection(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        // Laravel's unwrap(): Enumerable values are reduced to their underlying array.
        $this->assertSame([Role::Admin], EnumCollection::unwrap($collection));
    }

    public function testUnwrapOfArray(): void
    {
        $this->assertSame([Role::Admin], EnumCollection::unwrap([Role::Admin]));
    }

    public function testEmpty(): void
    {
        $collection = EnumCollection::empty();

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertTrue($collection->isEmpty());
    }

    public function testTimes(): void
    {
        $collection = EnumCollection::times(3, fn () => Role::Admin);

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertCount(3, $collection);
        $this->assertSame([Role::Admin, Role::Admin, Role::Admin], $collection->all());
    }

    public function testRange(): void
    {
        $collection = EnumCollection::range(1, 3);

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertSame([1, 2, 3], $collection->all());
    }

    public function testFromJson(): void
    {
        $collection = EnumCollection::fromJson('["admin","guest"]');

        $this->assertInstanceOf(EnumCollection::class, $collection);
        $this->assertSame(['admin', 'guest'], $collection->all());
    }

    // ─── basics ─────────────────────────────────────────────────────

    public function testAll(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testToArray(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Admin, Role::Guest], $collection->toArray());
    }

    public function testJsonSerialize(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Admin, Role::Guest], $collection->jsonSerialize());
    }

    public function testToJson(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame('["admin","guest"]', $collection->toJson());
    }

    public function testToPrettyJson(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertStringContainsString("\n", $collection->toPrettyJson());
    }

    public function testToString(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame('["admin","guest"]', (string) $collection);
    }

    public function testGetIterator(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertInstanceOf(ArrayIterator::class, $collection->getIterator());
        $this->assertSame([Role::Admin, Role::Guest], $collection->getIterator()->getArrayCopy());
    }

    public function testCount(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertCount(2, $collection);
    }

    public function testIsEmptyAndIsNotEmpty(): void
    {
        $this->assertTrue((new EnumCollection)->isEmpty());
        $this->assertFalse((new EnumCollection)->isNotEmpty());

        $this->assertFalse((new EnumCollection([Role::Admin]))->isEmpty());
        $this->assertTrue((new EnumCollection([Role::Admin]))->isNotEmpty());
    }

    public function testContainsOneItem(): void
    {
        $this->assertTrue((new EnumCollection([Role::Admin]))->containsOneItem());
        $this->assertFalse((new EnumCollection([Role::Admin, Role::Guest]))->containsOneItem());
    }

    public function testContainsManyItems(): void
    {
        $this->assertFalse((new EnumCollection([Role::Admin]))->containsManyItems());
        $this->assertTrue((new EnumCollection([Role::Admin, Role::Guest]))->containsManyItems());
    }

    public function testLazy(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertInstanceOf(LazyCollection::class, $collection->lazy());
        $this->assertSame([Role::Admin, Role::Guest], $collection->lazy()->all());
    }

    public function testCollect(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertInstanceOf(BaseCollection::class, $collection->collect());
        $this->assertSame([Role::Admin], $collection->collect()->all());
    }

    public function testToBase(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertInstanceOf(BaseCollection::class, $collection->toBase());
        $this->assertSame([Role::Admin], $collection->toBase()->all());
    }

    // ─── item access ────────────────────────────────────────────────

    public function testGet(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->get(0));
        $this->assertSame(Role::Guest, $collection->get(1));
        $this->assertNull($collection->get(2));
        $this->assertSame(Role::Admin, $collection->get(2, Role::Admin));
    }

    public function testGetOrPut(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertSame(Role::Admin, $collection->getOrPut(0, Role::Guest));
        $this->assertSame(Role::Guest, $collection->getOrPut(1, Role::Guest));
        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testFirst(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->first());
        $this->assertSame(Role::Guest, $collection->first(fn ($item) => $item === Role::Guest));
        $this->assertNull((new EnumCollection)->first());
    }

    public function testLast(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Guest, $collection->last());
        $this->assertSame(Role::Admin, $collection->last(fn ($item) => $item === Role::Admin));
        $this->assertNull((new EnumCollection)->last());
    }

    public function testValue(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame('admin', $collection->value('value'));
        $this->assertNull($collection->value('nope'));
    }

    public function testFirstWhere(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->firstWhere('value', 'admin'));
        $this->assertNull($collection->firstWhere('value', 'nope'));
    }

    public function testFirstOrFail(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertSame(Role::Admin, $collection->firstOrFail());

        $this->expectException(\Illuminate\Support\ItemNotFoundException::class);
        (new EnumCollection)->firstOrFail();
    }

    public function testSole(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertSame(Role::Admin, $collection->sole());
        $this->assertSame(Role::Admin, $collection->sole(fn ($item) => $item->value === 'admin'));
    }

    public function testSoleThrowsOnMultiple(): void
    {
        $this->expectException(\Illuminate\Support\MultipleItemsFoundException::class);

        (new EnumCollection([Role::Admin, Role::Guest]))->sole();
    }

    public function testHasSole(): void
    {
        $this->assertTrue((new EnumCollection([Role::Admin]))->hasSole());
        $this->assertFalse((new EnumCollection([Role::Admin, Role::Guest]))->hasSole());
        $this->assertFalse((new EnumCollection)->hasSole());
    }

    public function testNth(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active, Status::Pending]);

        $this->assertSame([Role::Admin, Status::Active], $collection->nth(2)->values()->all());
    }

    public function testBefore(): void
    {
        /** @var EnumCollection<int, \BackedEnum> $collection */
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->before(Role::Guest));
        $this->assertNull($collection->before(Role::Admin));
        $this->assertNull($collection->before(Status::Active));
    }

    public function testAfter(): void
    {
        /** @var EnumCollection<int, \BackedEnum> $collection */
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Guest, $collection->after(Role::Admin));
        $this->assertNull($collection->after(Role::Guest));
        $this->assertNull($collection->after(Status::Active));
    }

    public function testSearch(): void
    {
        /** @var EnumCollection<int, \BackedEnum> $collection */
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(1, $collection->search(Role::Guest));
        $this->assertFalse($collection->search(Status::Active));
    }

    public function testSearchWithCallback(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(1, $collection->search(fn ($item) => $item === Role::Guest));
    }

    // ─── mutation ───────────────────────────────────────────────────

    public function testAdd(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $collection->add(Role::Guest);

        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testPush(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $collection->push(Role::Guest, Status::Active);

        $this->assertSame([Role::Admin, Role::Guest, Status::Active], $collection->all());
    }

    public function testUnshift(): void
    {
        $collection = new EnumCollection([Role::Guest]);

        $collection->unshift(Role::Admin);

        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testPrepend(): void
    {
        $collection = new EnumCollection([Role::Guest]);

        $collection->prepend(Role::Admin);

        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testPut(): void
    {
        $collection = new EnumCollection();

        $collection->put(5, Role::Admin);

        $this->assertSame(Role::Admin, $collection->get(5));
    }

    public function testPull(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->pull(0));
        $this->assertSame([1 => Role::Guest], $collection->all());
    }

    public function testShift(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->shift());
        $this->assertSame([Role::Guest], $collection->values()->all());
    }

    public function testShiftMany(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $shifted = $collection->shift(2);

        $this->assertInstanceOf(EnumCollection::class, $shifted);
        $this->assertSame([Role::Admin, Role::Guest], $shifted->all());
        $this->assertSame([Status::Active], $collection->values()->all());
    }

    public function testPop(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Guest, $collection->pop());
        $this->assertSame([Role::Admin], $collection->values()->all());
    }

    public function testPopMany(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $popped = $collection->pop(2);

        // pop() collects removed items in the order they were array_pop'ed (last first).
        $this->assertInstanceOf(EnumCollection::class, $popped);
        $this->assertSame([Status::Active, Role::Guest], $popped->values()->all());
    }

    public function testSplice(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $removed = $collection->splice(1, 1, [Status::Pending]);

        $this->assertSame([Role::Guest], $removed->values()->all());
        $this->assertSame([Role::Admin, Status::Pending, Status::Active], $collection->values()->all());
    }

    public function testTransform(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->transform(fn ($item) => $item->value === 'admin' ? Role::Admin : Role::Guest);

        $this->assertSame($collection, $result);
        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testReplace(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $replaced = $collection->replace([0 => Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $replaced);
        $this->assertSame(Role::Guest, $replaced->get(0));
    }

    public function testReplaceRecursive(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $replaced = $collection->replaceRecursive([1 => Status::Active]);

        $this->assertInstanceOf(EnumCollection::class, $replaced);
        $this->assertSame(Status::Active, $replaced->get(1));
    }

    public function testPad(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $padded = $collection->pad(3, Role::Guest);

        $this->assertSame([Role::Admin, Role::Guest, Role::Guest], $padded->all());
    }

    // ─── filtering ──────────────────────────────────────────────────

    public function testFilter(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->filter(fn ($item) => $item === Role::Admin);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testReject(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->reject(fn ($item) => $item === Role::Admin);

        $this->assertSame([Role::Guest], $result->values()->all());
    }

    public function testPartition(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        [$matched, $rejected] = $collection->partition(fn ($item) => $item instanceof Role);

        $this->assertInstanceOf(EnumCollection::class, $matched);
        $this->assertInstanceOf(EnumCollection::class, $rejected);
        $this->assertCount(2, $matched);
        $this->assertCount(1, $rejected);
    }

    public function testWhere(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->where('value', 'admin');

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testWhereStrict(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->whereStrict('value', 'admin');

        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testWhereNull(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->whereNull('nonexistent');

        $this->assertTrue($result->isEmpty());

        $result = $collection->whereNull('value');

        $this->assertCount(1, $result);
    }

    public function testWhereNotNull(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->whereNotNull('value');

        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testWhereIn(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $result = $collection->whereIn('value', ['admin', 'guest']);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testWhereNotIn(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $result = $collection->whereNotIn('value', ['admin']);

        $this->assertCount(2, $result);
    }

    public function testWhereBetween(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        // Both 'admin' and 'guest' are in the inclusive range ['aaa', 'gzz'].
        $result = $collection->whereBetween('value', ['aaa', 'gzz']);

        $this->assertSame([Role::Admin, Role::Guest], $result->values()->all());
    }

    public function testWhereNotBetween(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        // Both 'admin' and 'guest' are inside the inclusive range, so nothing is excluded.
        $result = $collection->whereNotBetween('value', ['aaa', 'gzz']);

        $this->assertTrue($result->isEmpty());
    }

    public function testWhereInstanceOf(): void
    {
        $collection = new EnumCollection([Role::Admin, Status::Active]);

        $result = $collection->whereInstanceOf(Role::class);

        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testContains(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->contains(fn ($item) => $item === Role::Admin));
        $this->assertTrue($collection->contains('value', 'admin'));
    }

    public function testContainsStrictWithEnum(): void
    {
        /** @var EnumCollection<int, \BackedEnum> $collection */
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->containsStrict(Role::Admin));
        $this->assertFalse($collection->containsStrict(Status::Active));
    }

    public function testDoesntContain(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertTrue($collection->doesntContain(fn ($item) => $item === Role::Guest));
    }

    public function testDoesntContainStrict(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertTrue($collection->doesntContainStrict(Role::Guest));
    }

    public function testEvery(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->every(fn ($item) => $item instanceof Role));
        $this->assertFalse($collection->every(fn ($item) => $item === Role::Admin));
    }

    public function testSome(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->some(fn ($item) => $item === Role::Admin));
        $this->assertFalse($collection->some(fn ($item) => $item === Status::Active));
    }

    public function testSkip(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $this->assertSame([Role::Guest, Status::Active], $collection->skip(1)->values()->all());
    }

    public function testSkipUntil(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Guest], $collection->skipUntil(fn ($item) => $item === Role::Guest)->values()->all());
        $this->assertEmpty($collection->skipUntil(fn ($item) => false)->all());
    }

    public function testSkipWhile(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Guest], $collection->skipWhile(fn ($item) => $item === Role::Admin)->values()->all());
    }

    public function testTake(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $this->assertSame([Role::Admin, Role::Guest], $collection->take(2)->values()->all());
        $this->assertSame([Role::Guest, Status::Active], $collection->take(-2)->values()->all());
    }

    public function testTakeUntil(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Admin], $collection->takeUntil(fn ($item) => $item === Role::Guest)->values()->all());
    }

    public function testTakeWhile(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([Role::Admin], $collection->takeWhile(fn ($item) => $item === Role::Admin)->values()->all());
    }

    public function testSlice(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $this->assertSame([Role::Guest, Status::Active], $collection->slice(1)->values()->all());
        $this->assertSame([Role::Guest], $collection->slice(1, 1)->values()->all());
    }

    public function testChunk(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $chunks = $collection->chunk(2);

        $this->assertInstanceOf(BaseCollection::class, $chunks);
        $this->assertCount(2, $chunks);
        $this->assertSame([Role::Admin, Role::Guest], $chunks->first()->all());
        $this->assertInstanceOf(EnumCollection::class, $chunks->first());
    }

    public function testChunkWhile(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Admin, Role::Guest]);

        $chunks = $collection->chunkWhile(function ($current, $key, $chunk) {
            return $current === $chunk->last();
        });

        $this->assertCount(2, $chunks);
        $this->assertCount(2, $chunks->first());
        $this->assertCount(1, $chunks->last());
    }

    public function testSplit(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $groups = $collection->split(2);

        $this->assertInstanceOf(BaseCollection::class, $groups);
        $this->assertCount(2, $groups);
        $this->assertCount(2, $groups->first());
    }

    public function testSplitIn(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $groups = $collection->splitIn(2);

        $this->assertInstanceOf(BaseCollection::class, $groups);
        $this->assertCount(2, $groups);
    }

    public function testSliding(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $windows = $collection->sliding(2);

        $this->assertCount(2, $windows);
        $this->assertSame([Role::Admin, Role::Guest], $windows->first()->all());
    }

    public function testForPage(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active, Status::Pending]);

        $this->assertSame([Status::Active, Status::Pending], $collection->forPage(2, 2)->values()->all());
    }

    public function testOnly(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->only([0]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->all());
    }

    public function testExcept(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->except([0]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([1 => Role::Guest], $result->all());
    }

    // ─── mapping ────────────────────────────────────────────────────

    public function testMap(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->map(fn ($item) => $item->value);

        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertSame(['admin', 'guest'], $result->all());
    }

    public function testMapToDictionary(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->mapToDictionary(fn ($item) => ['roles' => $item->value]);

        $this->assertSame(['roles' => ['admin', 'guest']], $result->all());
    }

    public function testMapWithKeys(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->mapWithKeys(fn ($item) => [$item->value => $item->name]);

        $this->assertSame(['admin' => 'Admin', 'guest' => 'Guest'], $result->all());
    }

    public function testMapToGroups(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->mapToGroups(fn ($item) => ['value' => $item->value]);

        $this->assertInstanceOf(EnumCollection::class, $result->get('value'));
        $this->assertSame(['admin', 'guest'], $result->get('value')->all());
    }

    public function testFlatMap(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->flatMap(fn ($item) => [$item->value, $item->value]);

        $this->assertSame(['admin', 'admin'], $result->all());
    }

    public function testEach(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $visited = [];
        $collection->each(function ($item) use (&$visited) {
            $visited[] = $item;
        });

        $this->assertSame([Role::Admin, Role::Guest], $visited);
    }

    public function testReduce(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->reduce(fn ($carry, $item) => $carry . ',' . $item->value, '');

        $this->assertSame(',admin,guest', $result);
    }

    public function testReduceWithKeys(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->reduceWithKeys(fn ($carry, $item, $key) => $carry + $key, 0);

        $this->assertSame(1, $result);
    }

    public function testPipe(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->pipe(fn ($collection) => $collection->count());

        $this->assertSame(2, $result);
    }

    public function testPipeInto(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->pipeInto(BaseCollection::class);

        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testPipeThrough(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->pipeThrough([
            fn ($collection) => $collection->count(),
            fn ($count) => $count * 2,
        ]);

        $this->assertSame(4, $result);
    }

    public function testTap(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $tapped = null;
        $result = $collection->tap(function ($c) use (&$tapped) {
            $tapped = $c;
        });

        $this->assertSame($collection, $result);
        $this->assertSame($collection, $tapped);
    }

    public function testFlatten(): void
    {
        $collection = new EnumCollection([[Role::Admin, Role::Guest]]);

        $result = $collection->flatten(1);

        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertCount(2, $result);
    }

    public function testCollapse(): void
    {
        $collection = new EnumCollection([[Role::Admin], [Role::Guest]]);

        $this->assertSame([Role::Admin, Role::Guest], $collection->collapse()->values()->all());
    }

    public function testCollapseWithKeys(): void
    {
        $collection = new EnumCollection([[5 => Role::Admin], [6 => Role::Guest]]);

        $result = $collection->collapseWithKeys();

        $this->assertSame([5 => Role::Admin, 6 => Role::Guest], $result->all());
    }

    public function testCombine(): void
    {
        $collection = new EnumCollection(['admin', 'guest']);

        $result = $collection->combine([Role::Admin, Role::Guest]);

        $this->assertSame(['admin' => Role::Admin, 'guest' => Role::Guest], $result->all());
    }

    public function testUnion(): void
    {
        $collection = new EnumCollection([0 => Role::Admin]);

        $result = $collection->union([0 => Role::Guest, 1 => Status::Active]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame(Role::Admin, $result->get(0));
        $this->assertSame(Status::Active, $result->get(1));
    }

    public function testConcat(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->concat([Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin, Role::Guest], $result->values()->all());
    }

    public function testCrossJoin(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->crossJoin([Status::Active]);

        $this->assertCount(2, $result);
        $this->assertSame([
            [Role::Admin, Status::Active],
            [Role::Guest, Status::Active],
        ], $result->all());
    }

    public function testZip(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->zip([Status::Active, Status::Pending]);

        $this->assertCount(2, $result);
        $this->assertSame([Role::Admin, Status::Active], $result[0]->all());
        $this->assertSame([Role::Guest, Status::Pending], $result[1]->all());
    }

    public function testMultiply(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->multiply(3);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertCount(3, $result);
    }

    // ─── ordering ───────────────────────────────────────────────────

    public function testSortBy(): void
    {
        $collection = new EnumCollection([Role::Guest, Role::Admin]);

        $result = $collection->sortBy(fn ($item) => $item->value);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin, Role::Guest], $result->values()->all());
    }

    public function testSortByDesc(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->sortByDesc(fn ($item) => $item->value);

        $this->assertSame([Role::Guest, Role::Admin], $result->values()->all());
    }

    public function testSortKeys(): void
    {
        $collection = new EnumCollection([1 => Role::Guest, 0 => Role::Admin]);

        $result = $collection->sortKeys();

        $this->assertSame([Role::Admin, Role::Guest], $result->values()->all());
    }

    public function testSortKeysDesc(): void
    {
        $collection = new EnumCollection([0 => Role::Admin, 1 => Role::Guest]);

        $result = $collection->sortKeysDesc();

        $this->assertSame([Role::Guest, Role::Admin], $result->values()->all());
    }

    public function testSortKeysUsing(): void
    {
        $collection = new EnumCollection([1 => Role::Guest, 0 => Role::Admin]);

        $result = $collection->sortKeysUsing('strcmp');

        $this->assertSame([Role::Admin, Role::Guest], $result->values()->all());
    }

    public function testReverse(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->reverse();

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Guest, Role::Admin], $result->values()->all());
    }

    public function testShuffleKeepsItems(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Status::Active]);

        $result = $collection->shuffle();

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertCount(3, $result);
        foreach ($result->all() as $item) {
            $this->assertContains($item, [Role::Admin, Role::Guest, Status::Active]);
        }
    }

    public function testRandom(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertContains($collection->random(), [Role::Admin, Role::Guest]);

        $random = $collection->random(2);
        $this->assertInstanceOf(EnumCollection::class, $random);
        $this->assertCount(2, $random);
    }

    public function testUnique(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Role::Admin]);

        $result = $collection->unique();

        $this->assertCount(2, $result);
    }

    public function testUniqueStrict(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest, Role::Admin]);

        $result = $collection->uniqueStrict();

        $this->assertCount(2, $result);
    }

    public function testDuplicates(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Admin, Role::Guest]);

        $result = $collection->duplicates();

        $this->assertCount(1, $result);
    }

    public function testDuplicatesStrict(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Admin, Role::Guest]);

        $result = $collection->duplicatesStrict();

        $this->assertCount(1, $result);
    }

    public function testGroupBy(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->groupBy(fn ($item) => $item->value);

        $this->assertInstanceOf(BaseCollection::class, $result);
        $this->assertSame(Role::Admin, $result->get('admin')->first());
        $this->assertInstanceOf(EnumCollection::class, $result->get('admin'));
    }

    public function testKeyBy(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->keyBy(fn ($item) => $item->value);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame(Role::Admin, $result->get('admin'));
        $this->assertSame(Role::Guest, $result->get('guest'));
    }

    public function testKeys(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame([0, 1], $collection->keys()->all());
    }

    public function testValues(): void
    {
        $collection = new EnumCollection([5 => Role::Admin, 9 => Role::Guest]);

        $result = $collection->values();

        $this->assertSame([Role::Admin, Role::Guest], $result->all());
    }

    public function testPluckByValue(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->pluck('value');

        $this->assertSame(['admin', 'guest'], $result->all());
    }

    public function testPluckByName(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->pluck('name');

        $this->assertSame(['Admin', 'Guest'], $result->all());
    }

    // ─── associative diffs / intersects (parent) ────────────────────

    public function testDiffUsing(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->diffUsing([Role::Guest], fn ($a, $b) => $a->value <=> $b->value);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testDiffAssoc(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->diffAssoc([0 => Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testDiffKeys(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->diffKeys([1 => Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([0 => Role::Admin], $result->all());
    }

    public function testIntersectUsing(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->intersectUsing([Role::Admin], fn ($a, $b) => $a->value <=> $b->value);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testIntersectAssoc(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->intersectAssoc([0 => Role::Admin]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertCount(1, $result);
    }

    public function testIntersectByKeys(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->intersectByKeys([1 => Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([1 => Role::Guest], $result->all());
    }

    public function testMergeRecursive(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->mergeRecursive([Role::Guest]);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertCount(2, $result);
    }

    // ─── string / numeric ───────────────────────────────────────────

    public function testImplodeWithKey(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame('admin,guest', $collection->implode('value', ','));
    }

    public function testJoin(): void
    {
        $collection = new EnumCollection(State::cases());

        $this->assertSame('Draft, Published and Archived',
            $collection->join(', ', ' and ')
        );

        $collection = new EnumCollection([Role::Admin]);

        $this->assertSame('Admin', $collection->join(' and '));

        $collection = new EnumCollection(State::cases());

        $this->assertSame('Draft!, Published! and Archived!',
            $collection
                ->map(fn(State $state) => $state->name.'!')
                ->join(', ', ' and ')
        );
    }

    public function testSum(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        // strlen('admin') + strlen('guest') = 5 + 5
        $this->assertSame(10, $collection->sum(fn ($item) => strlen($item->value)));
    }

    public function testAvg(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        // (strlen('admin') + strlen('guest')) / 2 = (5 + 5) / 2
        $this->assertSame(5, $collection->avg(fn ($item) => strlen($item->value)));
    }

    public function testMin(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->min(fn ($item) => strlen($item->value));

        $this->assertSame(5, $result);
    }

    public function testMax(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->max(fn ($item) => strlen($item->value));

        $this->assertSame(5, $result);
    }

    public function testCountBy(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Admin, Role::Guest]);

        $result = $collection->countBy(fn ($item) => $item->value);

        $this->assertSame(['admin' => 2, 'guest' => 1], $result->all());
    }

    public function testPercentage(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $percentage = $collection->percentage(fn ($item) => $item === Role::Admin);

        $this->assertSame(50.0, $percentage);
    }

    // ─── conditionals ───────────────────────────────────────────────

    public function testWhenNotEmpty(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->whenNotEmpty(fn ($c) => $c->count() * 10, fn () => 0);

        $this->assertSame(10, $result);
    }

    public function testWhenEmpty(): void
    {
        $collection = new EnumCollection;

        $result = $collection->whenEmpty(fn ($c) => 'empty', fn () => 'not empty');

        $this->assertSame('empty', $result);
    }

    public function testUnlessEmpty(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        // unlessEmpty() runs the callback when the collection is NOT empty.
        $result = $collection->unlessEmpty(fn ($c) => 'not empty', fn () => 'empty');

        $this->assertSame('not empty', $result);
    }

    public function testUnlessNotEmpty(): void
    {
        $collection = new EnumCollection;

        $result = $collection->unlessNotEmpty(fn ($c) => 'empty', fn () => 'not empty');

        $this->assertSame('empty', $result);
    }

    public function testEnsureValid(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame($collection, $collection->ensure(Role::class));
    }

    // ─── misc / string casting / ArrayAccess ────────────────────────

    public function testEscapeWhenCastingToString(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        // Default (no escaping): plain JSON.
        $this->assertSame('["admin"]', (string) $collection);

        // With escaping enabled, JSON quotes are HTML-escaped.
        $collection->escapeWhenCastingToString();
        $this->assertSame('[&quot;admin&quot;]', (string) $collection);
    }

    public function testGetCachingIterator(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $this->assertInstanceOf(CachingIterator::class, $collection->getCachingIterator());
    }

    public function testOffsetExists(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertTrue($collection->offsetExists(0));
        $this->assertFalse($collection->offsetExists(5));
    }

    public function testOffsetGet(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(Role::Admin, $collection->offsetGet(0));
    }

    public function testOffsetSet(): void
    {
        $collection = new EnumCollection;

        $collection->offsetSet(0, Role::Admin);
        $collection[1] = Role::Guest;

        $this->assertSame([Role::Admin, Role::Guest], $collection->all());
    }

    public function testOffsetUnset(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $collection->offsetUnset(0);

        $this->assertSame([1 => Role::Guest], $collection->all());
    }

    // ─── remaining inherited methods ────────────────────────────────

    public function testAverageAlias(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $this->assertSame(5, $collection->average(fn ($item) => strlen($item->value)));
    }

    public function testMedian(): void
    {
        $collection = new EnumCollection(Rating::cases());

        $result = $collection->median();

        $this->assertSame(2, $result);

        $result = $collection->median('name');

        $this->assertSame('three', $result);

        $collection = new EnumCollection(State::cases());

        $result = $collection->median();

        $this->assertSame('Draft', $result);

    }

    public function testMode(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Admin, Role::Guest]);

        $result = $collection->mode();

        $this->assertSame([Role::Admin], $result);

        $result = $collection->mode('foobar');

        $this->assertSame([Role::Admin], $result);

    }

    public function testSort(): void
    {
        $collection = new EnumCollection([Role::Guest, Role::Admin]);

        $result = $collection->sort();

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin, Role::Guest], $result->values()->all());
    }

    public function testSortDesc(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->sortDesc();

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Guest, Role::Admin], $result->values()->all());
    }

    public function testFlip(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->flip();

        $this->assertSame(['admin' => 0, 'guest' => 1], $result->all());
    }

    public function testSelect(): void
    {
        /** @var EnumCollection<int, \BackedEnum> $collection */
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->select(['value']);

        $this->assertCount(2, $result);
        $this->assertSame([
            ['value' => Role::Admin->value],
            ['value' => Role::Guest->value],
        ], $result->all());
    }

    public function testWhereInStrict(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->whereInStrict('value', ['admin']);

        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testWhereNotInStrict(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->whereNotInStrict('value', ['admin']);

        $this->assertSame([Role::Guest], $result->values()->all());
    }

    public function testDot(): void
    {
        $collection = new EnumCollection([[Role::Admin, Role::Guest]]);

        $result = $collection->dot();

        $this->assertCount(2, $result);
    }

    public function testUndot(): void
    {
        $collection = new EnumCollection(['admin', 'guest']);

        $result = $collection->undot();

        $this->assertCount(2, $result);
    }

    public function testHasMany(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Admin, Role::Guest]);

        $this->assertTrue($collection->hasMany(fn ($item) => $item === Role::Admin));
        $this->assertFalse($collection->hasMany(fn ($item) => $item === Role::Guest));
    }

    public function testEachSpread(): void
    {
        $collection = new EnumCollection([[Role::Admin, 'admin'], [Role::Guest, 'guest']]);

        $visited = [];
        $collection->eachSpread(function ($role, $label) use (&$visited) {
            $visited[] = [$role, $label];
        });

        $this->assertCount(2, $visited);
    }

    public function testMapSpread(): void
    {
        $collection = new EnumCollection([[Role::Admin, 'admin'], [Role::Guest, 'guest']]);

        $result = $collection->mapSpread(fn ($role, $label) => $label . ':' . $role->value);

        $this->assertSame(['admin:admin', 'guest:guest'], $result->all());
    }

    public function testReduceSpread(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        [$admin, $guest] = $collection->reduceSpread(function ($admin, $guest, $item) {
            return [$admin + (int) ($item === Role::Admin), $guest + (int) ($item === Role::Guest)];
        }, 0, 0);

        $this->assertSame(1, $admin);
        $this->assertSame(1, $guest);
    }

    public function testDiffAssocUsing(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->diffAssocUsing(
            [0 => Role::Guest],
            fn ($a, $b) => $a->value <=> $b->value
        );

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([Role::Admin], $result->values()->all());
    }

    public function testDiffKeysUsing(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->diffKeysUsing([1 => Role::Guest], fn ($a, $b) => $a <=> $b);

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertSame([0 => Role::Admin], $result->all());
    }

    public function testIntersectAssocUsing(): void
    {
        $collection = new EnumCollection([Role::Admin]);

        $result = $collection->intersectAssocUsing(
            [0 => Role::Admin],
            fn ($a, $b) => $a->value <=> $b->value
        );

        $this->assertInstanceOf(EnumCollection::class, $result);
        $this->assertCount(1, $result);
    }

    public function testMapInto(): void
    {
        $collection = new EnumCollection([Role::Admin, Role::Guest]);

        $result = $collection->mapInto(MapInto::class);

        $this->assertCount(2, $result);
        $this->assertSame(Role::Admin, $result[0]->enum);
        $this->assertSame(Role::Guest, $result[1]->enum);
    }

    public function testProxyRegistersMethod(): void
    {
        EnumCollection::proxy('total');

        $collection = new EnumCollection([Role::Admin]);

        // After proxy() registration, __get() returns a HigherOrderCollectionProxy instead of throwing.
        $this->assertInstanceOf(
            \Illuminate\Support\HigherOrderCollectionProxy::class,
            $collection->total
        );
    }

    // ══════════════════ UnitEnum (non-backed) coverage ═════════════════

    public function testUnitEnumIntersectReturnsCommonItems(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $result = $collection->intersect([State::Draft, State::Archived]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(State::Draft));
    }

    public function testUnitEnumDiffReturnsItemsNotInGiven(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $result = $collection->diff([State::Draft]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->has(State::Published));
    }

    public function testUnitEnumMergeAddsNewItems(): void
    {
        $collection = new EnumCollection([State::Draft]);

        $result = $collection->merge([State::Published]);

        $this->assertCount(2, $result);
        $this->assertTrue($result->has(State::Draft));
        $this->assertTrue($result->has(State::Published));
    }

    public function testUnitEnumMergeOverwritesExistingByName(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $result = $collection->merge([State::Archived, State::Draft]);

        $this->assertCount(3, $result);
        $this->assertContains(State::Draft, $result->all());
        $this->assertContains(State::Archived, $result->all());
        $this->assertContains(State::Published, $result->all());

        $collection = new EnumCollection([Rating::one, Rating::two]);

        $result = $collection->merge([Rating::two, Rating::three]);

        $this->assertCount(3, $result);
        $this->assertContains(Rating::one, $result->all());
        $this->assertContains(Rating::two, $result->all());
        $this->assertContains(Rating::three, $result->all());

    }

    public function testUnitEnumHasReturnsTrueForExistingItem(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertTrue($collection->has(State::Draft));
    }

    public function testUnitEnumDoesntHaveReturnsTrueForMissingItem(): void
    {
        $collection = new EnumCollection([State::Draft]);

        $this->assertTrue($collection->doesntHave(State::Archived));
    }

    public function testUnitEnumHasAnyReturnsTrueIfSomeExist(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertTrue($collection->hasAny([State::Draft, State::Archived]));
    }

    public function testUnitEnumForgetRemovesItems(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published, State::Archived]);

        $result = $collection->forget(State::Published);

        $this->assertCount(2, $result);
        $this->assertTrue($result->has(State::Draft));
        $this->assertFalse($result->has(State::Published));
    }

    public function testUnitEnumToArray(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertSame([State::Draft, State::Published], $collection->toArray());
    }

    public function testUnitEnumAll(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertSame([State::Draft, State::Published], $collection->all());
    }

    public function testUnitEnumCount(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published, State::Archived]);

        $this->assertCount(3, $collection);
    }

    public function testUnitEnumFilter(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published, State::Archived]);

        $result = $collection->filter(fn ($item) => $item === State::Draft);

        $this->assertCount(1, $result);
        $this->assertSame([State::Draft], $result->values()->all());
    }

    public function testUnitEnumFirst(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertSame(State::Draft, $collection->first());
    }

    public function testUnitEnumMap(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $result = $collection->map(fn ($item) => $item->name);

        $this->assertSame(['Draft', 'Published'], $result->all());
    }

    public function testUnitEnumValues(): void
    {
        $collection = new EnumCollection([5 => State::Draft, 9 => State::Published]);

        $this->assertSame([State::Draft, State::Published], $collection->values()->all());
    }

    public function testUnitEnumKeys(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertSame([0, 1], $collection->keys()->all());
    }

    public function testUnitEnumContains(): void
    {
        /** @var EnumCollection<int, \UnitEnum> $collection */
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertTrue($collection->contains(State::Draft));
        $this->assertFalse($collection->contains(State::Archived));
    }

    public function testUnitEnumUnique(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published, State::Draft]);

        $result = $collection->unique();

        $this->assertCount(2, $result);
    }

    public function testUnitEnumGroupByClosure(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published, State::Archived]);

        /** @var EnumCollection<string, EnumCollection> $result */
        $result = $collection->groupBy(fn ($item) => str_starts_with($item->name, 'D') ? 'd' : 'other');

        $this->assertCount(2, $result);
        $this->assertSame(State::Draft, $result->get('d')->first());
    }

    public function testUnitEnumPluckName(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $this->assertSame(['Draft', 'Published'], $collection->pluck('name')->all());
    }

    public function testUnitEnumJsonSerialize(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        // jsonSerialize returns the raw enum objects
        $this->assertSame([State::Draft, State::Published], $collection->jsonSerialize());
    }

    public function testUnitEnumToJsonFailsForNonBackedEnum(): void
    {
        $collection = new EnumCollection([State::Draft]);

        $this->assertFalse($collection->toJson());
    }

    public function testUnitEnumMergeReturnsStaticType(): void
    {
        $collection = new EnumCollection([State::Draft]);

        $result = $collection->merge([State::Published]);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }

    public function testUnitEnumIntersectReturnsStaticType(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $result = $collection->intersect([State::Draft]);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }

    public function testUnitEnumDiffReturnsStaticType(): void
    {
        $collection = new EnumCollection([State::Draft, State::Published]);

        $result = $collection->diff([State::Draft]);

        $this->assertInstanceOf(EnumCollection::class, $result);
    }
}