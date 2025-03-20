<?php

namespace Codewiser\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class AsStructCollection implements Castable
{
    protected array $arguments = [];

    /**
     * Specify the collection and/or model for the cast.
     *
     * @param  class-string<Pivot>  $class
     * @param  null|class-string<Collection>  $collection
     */
    public static function using(string $class, string $collection = null): static
    {
        return new static($class, $collection);
    }

    /**
     * @param  array|class-string<Collection|Pivot>  $classes
     */
    public function __construct(string|array $classes)
    {
        $classes = is_array($classes) ? $classes : func_get_args();

        $this->arguments = array_filter($classes);
    }

    protected function cast(): string
    {
        return static::class.':'.implode(',', $this->arguments);
    }

    public function __toString(): string
    {
        return $this->cast();
    }

    /**
     * @param  class-string<Collection>  $collection
     */
    public function with(string $collection): static
    {
        $this->arguments = array_filter($this->arguments, fn($item) => ! is_a($item, Collection::class, true));

        $this->arguments[] = $collection;

        return $this;
    }

    public function required(): string
    {
        $this->arguments[] = 'required';

        return $this->cast();
    }

    public function nullable(): string
    {
        $this->arguments = array_filter($this->arguments, fn($item) => $item !== 'required');

        return $this->cast();
    }

    public static function castUsing(array $arguments): CastsAttributes
    {
        return new class($arguments) implements CastsAttributes {
            protected string $collectionClass;
            protected string $structClass;
            protected bool $required = false;

            public function __construct(protected array $arguments)
            {
                $this->collectionClass = Collection::class;

                foreach ($this->arguments as $argument) {
                    if (is_a($argument, Collection::class, true)) {
                        $this->collectionClass = $argument;
                    }
                    if (is_a($argument, Pivot::class, true)) {
                        $this->structClass = $argument;
                    }
                }

                $this->required = in_array('required', $this->arguments);
            }

            public function get($model, $key, $value, $attributes)
            {
                if (is_null($value) && $this->required) {
                    $value = [];
                }

                if (is_string($value)) {
                    $value = Json::decode($value);
                }

                if (is_array($value)) {
                    $value = array_map(fn($item) => new $this->structClass($item), $value);
                }

                return is_array($value) ? new $this->collectionClass($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return Json::encode($value);
            }
        };
    }
}
