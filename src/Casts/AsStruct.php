<?php

namespace Codewiser\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class AsStruct implements Castable
{
    protected array $arguments = [];

    /**
     * Specify the class for the cast.
     *
     * @param  class-string<Pivot>  $class
     */
    public static function using(string $class): static
    {
        return new static($class);
    }

    /**
     * Specify the collection and/or model for the cast.
     *
     * @param  class-string<Pivot>  $class
     * @param  null|class-string<Collection>  $collection
     */
    public static function collects(string $class, string $collection = null): AsStructCollection
    {
        return new AsStructCollection($class, $collection);
    }

    /**
     * @param  class-string<Pivot>  $class
     */
    public function __construct(string $class)
    {
        $this->arguments = [$class];
    }

    protected function cast(): string
    {
        return static::class.':'.implode(',', $this->arguments);
    }

    public function __toString(): string
    {
        return $this->cast();
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
            protected string $structClass;
            protected bool $required = false;

            public function __construct(protected array $arguments)
            {
                foreach ($this->arguments as $argument) {
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

                return is_array($value) ? new $this->structClass($value) : null;
            }

            public function set($model, $key, $value, $attributes)
            {
                return Json::encode($value);
            }
        };
    }
}
