<?php

namespace Codewiser\Enum;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Concerns\InteractsWithDictionary;
use Illuminate\Support\Collection as BaseCollection;
use UnitEnum as TValue;
use function Illuminate\Support\enum_value;

/**
 * @template TKey of array-key
 *
 * @template TValue of \UnitEnum
 *
 * @extends \Illuminate\Support\Collection<TKey, TValue>
 */
class Collection extends BaseCollection
{
    /**
     * Get a dictionary key attribute - casting it to a string if necessary.
     *
     * @param  mixed  $attribute
     * @return string|int|null
     *
     * @throws \InvalidArgumentException
     */
    protected function getDictionaryKey($attribute): int|string|null
    {
        if (is_null($attribute) || is_string($attribute) || is_int($attribute)) {
            return $attribute;
        }

        if (is_object($attribute)) {
            if (method_exists($attribute, '__toString')) {
                return $attribute->__toString();
            }
            if ($attribute instanceof \BackedEnum) {
                return $attribute->value;
            }
            if ($attribute instanceof \UnitEnum) {
                return $attribute->name;
            }

            throw new \InvalidArgumentException('Model attribute value is an object but does not have a __toString method.');
        }

        return (string) $attribute;
    }

    /**
     * Run a map over each of the items.
     *
     * @template TMapValue
     *
     * @param  callable(TValue, TKey): TMapValue  $callback
     *
     * @return \Illuminate\Support\Collection<TKey, TMapValue>|static<TKey, TMapValue>
     */
    public function map(callable $callback): BaseCollection
    {
        $result = parent::map($callback);

        return $result->contains(fn($item) => ! $item instanceof \UnitEnum) ? $result->toBase() : $result;
    }

    /**
     * Intersect the collection with the given items.
     *
     * @param  iterable<array-key, TValue>  $items
     */
    public function intersect($items): static
    {
        $intersect = new static;

        if (empty($items)) {
            return $intersect;
        }

        foreach ($this->items as $item) {
            if (in_array($item, $items, true)) {
                $intersect->add($item);
            }
        }

        return $intersect;
    }

    /**
     * Diff the collection with the given items.
     *
     * @param  iterable<array-key, TValue>  $items
     */
    public function diff($items): static
    {
        $items = $this->getArrayableItems($items);

        $diff = new static;

        foreach ($this->items as $item) {
            if (! in_array($item, $items, true)) {
                $diff->add($item);
            }
        }

        return $diff;
    }

    /**
     * Merge the collection with the given items.
     *
     * @param  iterable<array-key, TValue>  $items
     */
    public function merge($items): static
    {
        $items = $this->getArrayableItems($items);

        $merged = new static($this->items);

        foreach ($items as $item) {
            if (! in_array($item, $merged->items, true)) {
                $merged->add($item);
            }
        }

        return $merged;
    }

    /**
     * Determine if an item exists in the collection by value.
     *
     * @param  TValue|array<array-key, TValue>  $value
     */
    public function has($value): bool
    {
        $values = is_array($value) ? $value : func_get_args();

        return array_all($values, fn($value) => in_array($value, $this->items, true));
    }

    /**
     * Determine if an item missing in the collection by value.
     *
     * @param  TValue|array<array-key, TValue>  $value
     */
    public function doesntHave($value): bool
    {
        $values = is_array($value) ? $value : func_get_args();

        return array_all($values, fn($value) => ! in_array($value, $this->items, true));
    }

    /**
     * Determine if any of the values exist in the collection.
     *
     * @param  TValue|array<array-key, TValue>  $value
     */
    public function hasAny($value): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        $values = is_array($value) ? $value : func_get_args();

        return array_any($values, fn($value) => in_array($value, $this->items, true));
    }

    /**
     * Remove an item from the collection by value.
     *
     * @param  Arrayable<array-key, TValue>|iterable<array-key, TValue>|TValue  $values
     */
    public function forget($values): static
    {
        $values = $this->getArrayableItems($values);

        $keys = $this
            ->filter(fn($item) => in_array($item, $values, true))
            ->keys()
            ->toArray();

        return parent::forget($keys);
    }

    /**
     * Filter items where the value for the given key is null.
     *
     * @param  string|null  $key
     *
     * @return static
     */
    public function whereNull($key = null): static
    {
        return $this->filter(fn($item) => $key
            ? (property_exists($item, $key) && $item->{$key} !== null)
            : is_null($item)
        );
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     *
     * @return static
     */
    public function diffAssoc($items): static
    {
        $items = $this->getArrayableItems($items);

        $diff = new static;

        foreach ($this->items as $key => $value) {
            if (! isset($items[$key]) || $items[$key] !== $value) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Get the items in the collection whose keys and values are not present in the given items, using the callback.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     * @param  callable(TValue, TValue): int  $callback
     *
     * @return static
     */
    public function diffAssocUsing($items, callable $callback): static
    {
        $items = $this->getArrayableItems($items);

        $diff = new static;

        foreach ($this->items as $key => $value) {
            if (! isset($items[$key]) || $callback($value, $items[$key]) !== 0) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

    /**
     * Intersect the collection with the given items with additional index check.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     *
     * @return static
     */
    public function intersectAssoc($items): static
    {
        $items = $this->getArrayableItems($items);

        $intersect = new static;

        foreach ($this->items as $key => $value) {
            if (isset($items[$key]) && $items[$key] === $value) {
                $intersect[$key] = $value;
            }
        }

        return $intersect;
    }

    /**
     * Intersect the collection with the given items with additional index check, using the callback.
     *
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, TValue>|iterable<TKey, TValue>  $items
     * @param  callable(TValue, TValue): int  $callback
     *
     * @return static
     */
    public function intersectAssocUsing($items, callable $callback): static
    {
        $items = $this->getArrayableItems($items);

        $intersect = new static;

        foreach ($this->items as $key => $value) {
            if (isset($items[$key]) && $callback($value, $items[$key]) === 0) {
                $intersect[$key] = $value;
            }
        }

        return $intersect;
    }

    /**
     * Get the mode. Key is ignored.
     *
     * @param  string|array<array-key, string>|null  $key
     *
     * @return array<int, TValue>|null
     */
    public function mode($key = null): ?array
    {
        if ($this->count() === 0) {
            return null;
        }

        $collection = $this;

        $counts = new static;

        $byValue = [];

        $collection->each(function ($value) use ($counts, &$byValue) {
            $dictionaryKey = $this->getDictionaryKey($value);

            $counts[$dictionaryKey] = isset($counts[$dictionaryKey]) ? $counts[$dictionaryKey] + 1 : 1;
            $byValue[$dictionaryKey] = $value;
        });

        $sorted = $counts->sort();

        $highestCount = $sorted->last();

        return $sorted->filter(fn($count) => $count == $highestCount)
            ->sort()
            ->keys()
            ->map(fn($dictionaryKey) => $byValue[$dictionaryKey])
            ->all();
    }

    /**
     * Join all items from the collection using a string. The final items can use a separate glue string.
     *
     * @param  string  $glue
     * @param  string  $finalGlue
     *
     * @return string
     */
    public function join($glue, $finalGlue = ''): string
    {
        if ($finalGlue === '') {
            return $this->implode('name', $glue);
        }

        $count = $this->count();

        if ($count === 0) {
            return '';
        }

        if ($count === 1) {
            return $this->pluck('name')->last();
        }

        $collection = new static($this->items);

        $finalItem = $collection->pop()?->name;

        return $collection->implode('name', $glue).$finalGlue.$finalItem;
    }

    /**
     * Get the median of a given key. Key is required!
     *
     * @param  string|array<array-key, string>|null  $key
     *
     * @return string|float|int|null
     */
    public function median($key = null): string|float|int|null
    {
        if (is_null($key) && $this->first() instanceof \BackedEnum) {
            $key = 'value';
        }

        return parent::median($key ?? 'name');
    }

    /**
     * Sort items in ascending order. Unless a callback is given, unit enums are sorted by name, backed enums by value.
     *
     * @param  (callable(TValue, TValue): int)|null  $callback
     *
     * @return static
     */
    public function sort($callback = null): static
    {
        $items = $this->items;

        $callback && is_callable($callback)
            ? uasort($items, $callback)
            : uasort($items, fn($a, $b) => $this->getDictionaryKey($a) <=> $this->getDictionaryKey($b));

        return new static($items);
    }

    /**
     * Sort items in descending order. Unit enums are sorted by name, backed enums by value.
     *
     * @param  int  $options
     *
     * @return static
     */
    public function sortDesc($options = SORT_REGULAR): static
    {
        $items = $this->items;

        uasort($items, fn($a, $b) => $this->getDictionaryKey($b) <=> $this->getDictionaryKey($a));

        return new static($items);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function collapse(): BaseCollection
    {
        return $this->toBase()->collapse();
    }

    /**
     * {@inheritDoc}
     *
     * @return \Illuminate\Support\Collection<int, mixed>
     */
    public function flatten($depth = INF): BaseCollection
    {
        return $this->toBase()->flatten($depth);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Illuminate\Support\Collection<string|int, TKey>
     */
    public function flip(): BaseCollection
    {
        return $this
            ->map(function ($item) {
                $k = $item;
                if ($item instanceof \BackedEnum) {
                    $k = $item->value;
                } elseif ($item instanceof \UnitEnum) {
                    $k = $item->name;
                }
                return $k;
            })
            ->toBase()
            ->flip();
    }

    /**
     * {@inheritDoc}
     *
     * @return \Illuminate\Support\Collection<int, TKey>
     */
    public function keys(): BaseCollection
    {
        return $this->toBase()->keys();
    }

    /**
     * {@inheritDoc}
     *
     * @template TPadValue
     *
     * @return \Illuminate\Support\Collection<int, TValue|TPadValue>
     */
    public function pad($size, $value): BaseCollection
    {
        return $this->toBase()->pad($size, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @return \Illuminate\Support\Collection<int<0, 1>, static<TKey, TValue>>
     */
    public function partition($key, $operator = null, $value = null): BaseCollection
    {
        return parent::partition(...func_get_args())->toBase();
    }

    /**
     * {@inheritDoc}
     *
     * @return \Illuminate\Support\Collection<array-key, mixed>
     */
    public function pluck($value, $key = null): BaseCollection
    {
        return $this->toBase()->pluck($value, $key);
    }

    /**
     * {@inheritDoc}
     *
     * @template TZipValue
     *
     * @return \Illuminate\Support\Collection<int, \Illuminate\Support\Collection<int, TValue|TZipValue>>
     */
    public function zip($items): BaseCollection
    {
        return $this->toBase()->zip(...func_get_args());
    }
}
