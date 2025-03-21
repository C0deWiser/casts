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
    public array $type_of;

    public function __construct(string|array $type_of)
    {
        $this->type_of = is_array($type_of) ? $type_of : [$type_of];
    }

    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (!$value || !$this->type_of) {
            // Nothing to validate
            return;
        }

        $model = Relation::getMorphedModel($value) ?? $value;

        if (class_exists($model)) {
            $match = false;

            foreach ($this->type_of as $classname) {
                if (is_a($model, $classname, true)) {
                    $match = true;
                }
            }

            if (!$match) {
                $fail(__("The :attribute should be one of [:types]", [
                    'types' => implode(', ', $this->type_of)
                ]));
            }
        } else {
            $fail(__("The :attribute should be a Model class or alias"));
        }
    }
}
