# Enum Collections

- [Introduction](#introduction)
- [Available Methods](#available-methods)

## Introduction

The `EnumCollection` class extends Laravel's 
[base collection](https://laravel.com/framework/docs/collections), 
so it inherits every method used to fluently work with the underlying array 
of `BackedEnum` / `UnitEnum` cases.

### Enum Collection Conversion

While most Enum collection methods return a new instance of an Enum collection,
the `collapse`, `flatten`, `flip`, `keys`, `pluck`, and `zip` methods return 
a [base collection](https://laravel.com/framework/docs/collections),
instance. 
Likewise, if a `map` operation returns a collection that does not contain any
Enum objects, it will be converted to a base collection instance.

## Available Methods

[diff](#method-diff)  
[doesntHave](#method-doesnt-have)  
[forget](#method-forget)  
[has](#method-has)  
[hasAny](#method-has-any)  
[intersect](#method-intersect)  
[join](#method-join)  
[merge](#method-merge)  
[sort](#method-sort)  
[sortDesc](#method-sort-desc)

All other methods of base collection, like `contains`, `doesntContain`, etc. 
works with enum values without modifications.

<a name="method-diff"></a>
#### `diff($items)`

The `diff` method returns all of the enums that are not present in the given 
collection.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin, Role::Guest, Role::Viewer]);

$roles = $roles->diff([Role::Guest]);

// [Role::Admin, Role::Viewer]
```

<a name="method-doesnt-have"></a>
#### `doesntHave($value)`

The `doesntHave` method determines if one or more values are missing from 
the collection.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin]);

$roles->doesntHave([Role::Guest, Role::Viewer]); // true

$roles->doesntHave([Role::Admin, Role::Guest]); // false
```

<a name="method-forget"></a>
#### `forget($values)`

The `forget` method removes items from the collection **by value**, unlike 
the base collection's `forget` method, which removes items by key.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin, Role::Guest]);

$roles->forget(Role::Guest);

// [Role::Admin]
```

<a name="method-has"></a>
#### `has($value)`

The `has` method determines if one or more values exist in the collection. 
Unlike the base collection's `has` method, which checks for the presence of 
a key, this method checks for the presence of a value.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin]);

$roles->has(Role::Admin); // true

$roles->has([Role::Admin, Role::Guest]); // false
```

<a name="method-has-any"></a>
#### `hasAny($value)`

The `hasAny` method determines if any of the given values exist in the 
collection.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin]);

$roles->hasAny([Role::Admin, Role::Guest]); // true

$roles->hasAny([Role::Guest, Role::Viewer]); // false
```

<a name="method-intersect"></a>
#### `intersect($items)`

The `intersect` method returns all of the enums that are also present in the 
given collection.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin, Role::Guest, Role::Viewer]);

$roles = $roles->intersect([Role::Guest, Role::Viewer]);

// [Role::Guest, Role::Viewer]
```

<a name="method-join"></a>
#### `join($glue, $finalGlue = '')`

The `join` method joins the collection's values with a string. The final 
item may use a separate glue string. Items are joined using the enum's name.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\State;

$states = new EnumCollection([State::Draft, State::Published, State::Archived]);

$states->join(', ', ' and ');

// 'Draft, Published and Archived'
```

<a name="method-merge"></a>
#### `merge($items)`

The `merge` method merges the given items into the collection, skipping any 
enum case that is already present in the collection.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;
use App\Enums\Status;

$roles = new EnumCollection([Role::Admin]);

$roles = $roles->merge([Role::Guest, Status::Pending]);

// [Role::Admin, Role::Guest, Status::Pending]
```

<a name="method-sort"></a>
#### `sort($callback = null)`

The `sort` method sorts the collection, preserving its keys.
Explicitly, unit enums are sorted by their `name`, 
and backed enums are sorted by their `value`.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Guest, Role::Admin]);

$roles = $roles->sort();

// [Role::Admin, Role::Guest]
```

<a name="method-sort-desc"></a>
#### `sortDesc($options = SORT_REGULAR)`

The `sortDesc` method sorts the collection in the opposite order.

```php
use Codewiser\Collections\EnumCollection;
use App\Enums\Role;

$roles = new EnumCollection([Role::Admin, Role::Guest]);

$roles = $roles->sortDesc();

// [Role::Guest, Role::Admin]
```