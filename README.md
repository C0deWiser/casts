# Custom Casts

## Structures

Structure is an `array` or `json` attribute cast to an object.

For example:

```php
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Stringable;

/**
 * @property  null|Stringable  $first_name
 * @property  null|Stringable  $second_name
 * @property  null|Stringable  $family_name
 */
class Username extends Pivot
{
    protected function casts(): array
    {
        return [
            'first_name'  => AsStringable::class,
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
            'name' => AsStruct::using(Username::class)->nullable()
        ];
    }    
}
```

Now, the IDE you are using may suggest structure attributes:

```php
$user->name->first_name;
```

You can make it non-nullable. It means that `name` attribute will always be an 
object, even if empty.

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
            'name' => AsStruct::using(Username::class)->required()
        ];
    }
}
```

> Structures may be nested.

## Structure collections

The same way you may cast collections of custom structs:

```php
use Codewiser\Casts\AsStruct;
use Illuminate\Support\Collection;

/**
 * @property null|ContactCollection<int,Contact> $contacts_1
 * @property null|Collection<int,Contact> $contacts_2
 * @property Collection<int,Contact> $contacts_3
 */
class User extends Model
{
    protected function casts(): array
    {
        return [
            'contacts_1' => AsStruct::collects(Contact::class, ContactCollection::class)->nullable(),
            'contacts_2' => AsStruct::collects(Contact::class)->nullable(),
            'contacts_3' => AsStruct::collects(Contact::class)->required(),
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

## Request morph validator

### Before

```php
use Illuminate\Database\Eloquent\Relations\Relation;

class MyRequest extends FormRequest 
{
    public function rules(): array 
    {
        return [
            'commentable_type' => 'required|string',
            'commentable_id'   => 'required',
        ];
    }
    
    public function getCommentable(): Model 
    {
        $class = Relation::getMorphedModel($this->input('commentable_type'));
        
        return $class::find($this->integer('commentable_id'));
    }
}
```

### After

```php
use Codewiser\Requests\HasMorphs;
use Illuminate\Database\Eloquent\Relations\Relation;

class MyRequest extends FormRequest 
{
    use HasMorphs;

    public function rules(): array 
    {
        return [
            ...$this->morph('commentable')
        ];
    }
    
    public function getCommentable(): Model 
    {
        return $this->morphed('commentable');
    }
}
```

Additionally, you may pass list of classes, the morphed model could be.

```php
use Codewiser\Requests\HasMorphs;
use Illuminate\Database\Eloquent\Relations\Relation;

class MyRequest extends FormRequest 
{
    use HasMorphs;

    public function rules(): array 
    {
        return [
            ...$this->nullableMorph('commentable', [Post::class, Article::class])
        ];
    }
    
    public function hasCommentable(): bool {
        return $this->hasMorph('commentable');
    }
    
    public function getCommentable(): ?Model 
    {
        return $this->morphed('commentable');
    }
}
```