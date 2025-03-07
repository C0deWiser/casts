<?php

namespace Codewiser\Casts;

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class AsStruct implements Castable
{
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

    /**
     * Specify the class for the cast.
     *
     * @param  class-string<Pivot>  $struct
     * @param  bool  $nullable
     *
     * @return string
     */
    public static function using(string $struct, bool $nullable = true): string
    {
        $args = [
            $struct,
            $nullable ? 'nullable' : 'required',
        ];

        return static::class.':'.implode(',', $args);
    }

    /**
     * Specify the collection and/or model for the cast.
     *
     * @param  class-string<Collection|Pivot>  $class
     * @param  null|class-string<Pivot>  $struct
     * @param  bool  $nullable
     *
     * @return string
     */
    public static function collection(string $class, string $struct = null, bool $nullable = true): string
    {
        return AsStructCollection::using($class, $struct, $nullable);
    }
}
