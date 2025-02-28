# Custom Casts

## Structures

Structure is an `array` or `json` attribute with structured interface.

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

Now, the IDE you are using may suggest structure attributes:

```php
$user->name->first_name;
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

> Note that structures way be nested.

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

## Date-time with timezone

Laravel doesn't respect timezone. 
Cast `\Codewiser\Casts\AsDatetimeWithTZ` fixes this behaviour.

### Before

```php
class Article extends \Illuminate\Database\Eloquent\Model
{
    protected $casts = [
        'date' => 'datetime'
    ];
}
```

```php
// e.g. Laravel has Europe/London (+01:00) timezone
config()->set('app.timezone', 'Europe/London');

$model = new Article();

$model->date = '2000-01-01T10:00:00+02:00';

echo $model->date->format('c');
// Expecting 2000-01-01T09:00:00+01:00
// Actual    2000-01-01T10:00:00+01:00
```

### After

```php
class Article extends \Illuminate\Database\Eloquent\Model
{
    protected $casts = [
        'date' => \Codewiser\Casts\AsDatetimeWithTZ::class
    ];
}
```

```php
// e.g. Laravel has Europe/London (+01:00) timezone
config()->set('app.timezone', 'Europe/London');

$model = new Article();

$model->date = '2000-01-01T10:00:00+02:00';

echo $model->date->format('c');
// Expecting 2000-01-01T09:00:00+01:00
// Actual    2000-01-01T09:00:00+01:00
```