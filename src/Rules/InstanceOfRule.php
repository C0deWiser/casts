<?php

namespace Codewiser\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Validator;

/**
 * The value under validation is a morph name, and it should be one of given classes.
 */
class InstanceOfRule implements ValidationRule
{
    public function __construct(public string|array $type_of)
    {
        //
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        $model = Relation::getMorphedModel($value);

        $type_of = is_array($this->type_of) ? $this->type_of : [$this->type_of];

        if ($model) {
            $model = new $model;

            $match = false;

            foreach ($type_of as $classname) {
                if ($model instanceof $classname) {
                    $match = true;
                }
            }

            if (!$match) {
                $fail(__("The :attribute should be one of :types", [
                    'types' => implode(', ', $type_of)
                ]));
            }
        }
    }
}
