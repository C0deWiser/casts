## Structures

Structure is an `array` or `json` attributes with structured interface.

For example:

```php
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Stringable;

/**
 * @property null|Stringable $first_name
 * @property null|Stringable $second_name
 * @property null|Stringable $family_name
 */
class Username extends Pivot
{
    protected function casts(): array
    {
        return [
            'first_name' => AsStringable::class,
            'second_name' => AsStringable::class,
            'family_name' => AsStringable::class,
        ];   
    }
} 
```

> Note, that it is not a real Model. We use Pivot as it has no key and has 
> attributes, casts etc. We will never save it. We use it just as structure 
> interface.

Apply `Username` struct to `User` model:

```php
use Codewiser\Casts\AsStruct;
use Illuminate\Database\Eloquent\Model;

/**
 * @property null|Username $name
 */
class User extends Model
{
    protected function casts(): array
    {
        return [
            'name' => AsStruct::using(Username::class)
        ];
    }    
}
```

You can make it not-nullable:

```php
use Codewiser\Casts\AsStruct;
use Illuminate\Database\Eloquent\Model;

/**
 * @property Username $name
 */
class User extends Model
{
    protected function casts(): array
    {
        return [
            'name' => AsStruct::using(Username::class, required: true)
        ];
    }    
}
```

## Structure collections

The same way, you may cast collections of custom structs:

```php
use Codewiser\Casts\AsStructCollection;
use Illuminate\Support\Collection;

/**
 * @property null|Collection<Contact> $contacts_1
 * @property null|ContactCollection<Contact> $contacts_2
 * @property Collection<Contact> $contacts_3
 */
class User extends Model
{
    protected function casts(): array
    {
        return [
            'contacts_1' => AsStructCollection::using(Contact::class),
            'contacts_2' => AsStructCollection::using(ContactCollection::class, Contact::class),
            'contacts_3' => AsStructCollection::using(Contact::class, required: true),
        ];
    }    
}
```