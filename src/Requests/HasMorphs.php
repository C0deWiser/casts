<?php

namespace Codewiser\Requests;

use Codewiser\Rules\InstanceOfRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

trait HasMorphs
{
    /**
     * Get morphed object.
     */
    public function morphed(string $name): mixed
    {
        if ($type = $this->input("{$name}_type")) {
            if ($model = Relation::getMorphedModel($type)) {
                return call_user_func("$model::find", $this->input("{$name}_id", 0));
            }
        }

        return null;
    }

    /**
     * Validation rules for morphed model.
     *
     * @param  string  $name  Morph (class alias).
     * @param  class-string<Model>|array<array-key,class-string<Model>>|null  $of  Model should be instance of given class(es).
     */
    public function morph(string $name, null|string|array $of = null, bool $required = true): array
    {
        $exists = null;

        if ($alias = $this->input("{$name}_type")) {
            $class = Relation::getMorphedModel($alias) ?? $alias;
            if (class_exists($class)) {
                $model = new $class;
                if ($model instanceof Model) {
                    $exists = $model->getTable().','.$model->getKeyName();
                }
            }
        }

        if (is_null($of)) {
            $of = [];
        }
        if (is_string($of)) {
            $of = [$of];
        }

        $id_rules = [
            $required ? 'required' : 'nullable',
            $required ? null : "required_with:{$name}_type",
            $exists ? "exists:$exists" : '',
        ];
        $type_rules = [
            $required ? 'required' : 'nullable',
            $required ? null : "required_with:{$name}_id",
            new InstanceOfRule($of),
        ];

        return [
            "{$name}_id"   => array_values(array_filter($id_rules)),
            "{$name}_type" => array_values(array_filter($type_rules)),
        ];
    }

    /**
     * Validation rules for morphed model (nullable).
     *
     * @param  string  $name  Morph (class alias).
     * @param  class-string<Model>|array<array-key,class-string<Model>>|null  $of  Model should be instance of given class(es).
     */
    public function nullableMorph(string $name, null|string|array $of = null): array
    {
        return $this->morph($name, $of, false);
    }

    /**
     * Check is request has a morph.
     */
    protected function hasMorph(string $name): bool
    {
        return $this->has("{$name}_id", "{$name}_type");
    }
}
