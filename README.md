# Custom Casts

- [Object Casting](#object-casting)
- [Enum Collections](#enum-collections)
- [Date-time with timezone Casting](#date-time-with-timezone-casting)
- [Form Request `morph` Validator](#form-request-morph-validator)

## Object Casting

Just like Laravel allows to 
[cast data to a Collection](https://laravel.com/framework/docs/12.x/eloquent-mutators#array-object-and-collection-casting), 
it allows to cast data into an object.

```php
use App\Collections\OptionCollection;
use Codewiser\Casts\AsObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Get the attributes that should be cast.
 *
 * @return array<string, string>
 */
protected function casts(): array
{
    return [
        'option'  => AsObject::of(Option::class),
        'options' => AsCollection::using(OptionCollection::class, Option::class),
    ];
}
```

## Enum Collections

Laravel provides `AsEnumCollection` to cast array of enum values into a
collection, but actually it is REDUNDANT. It is more than enough to use 
`AsCollection:of(Enum:class)`.

Much worse, that base collection can't handle enum values in some cases. For 
example, `intersect` or `diff` fails, as `array_intersect` doesn't support 
enums.

This package provides [enum collection](enum-collections.md), where these 
methods are fixed to work with enums.

You may cast enum collection this way:

```php
use App\Collections\OptionCollection;
use Codewiser\Collections\EnumCollection;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Get the attributes that should be cast.
 *
 * @return array<string, string>
 */
protected function casts(): array
{
    return [
        'roles' => AsCollection::using(EnumCollection::class, RoleEnum::class),
    ];
}
```

## Date-time with timezone Casting

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

## Form Request `morph` Validator

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
    
    public function getCommentable(): null|Post|Article 
    {
        return $this->morphed('commentable');
    }
}
```