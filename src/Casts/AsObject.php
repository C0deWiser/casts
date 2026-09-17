<?php

namespace Codewiser\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;

class AsObject implements CastsAttributes
{
    /**
     * Specify the type of object.
     *
     * @param  class-string  $class
     * @return string
     */
    public static function of(string $class): string
    {
        return static::class.':'.$class;
    }

    public function __construct(protected string $static)
    {
        //
    }

    public function get(Model $model, string $key, mixed $value, array $attributes)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (is_array($value)) {
            return new $this->static($value);
        }

        return null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes)
    {
        if ($value instanceof \JsonSerializable) {
            $value = $value->jsonSerialize();
        }

        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }

        if (is_array($value)) {
            $value = json_encode($value);
        }

        return $value;
    }
}