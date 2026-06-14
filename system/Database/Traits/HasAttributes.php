<?php

namespace System\Database\Traits;

use Collator;
use System\Database\DBConnection\DBConnection;

trait HasAttributes
{

    private function registerAttribute(Object $object, string $attribute, mixed $value): void
    {
        $object->{$attribute} = $this->inCastAttribute($attribute)
            ? $this->castDecodeValue($attribute, $value)
            : $value;
    }

    protected function arrayToAttribute(array $array, ?Object $object = null): Object
    {
        if(!$object){
            $object = new (static::class);
        }
        foreach ($array as $attribute => $value) {
            if ($this->inHiddenAttribute($attribute)){
                continue;
            }

            $this->registerAttribute($object, $attribute, $value);
        }
        return $object;
    }

    protected function arrayToObject(array $records): void
    {
        $collection = [];
        foreach ($records as $record) {
            $object = $this->arrayToAttribute($record);
            $collection[] = $object;
        }
        $this->collection = $collection;
    }

    private function inHiddenAttribute($attribute): bool
    {
        return in_array($attribute, $this->hidden, true);
    }

    private function inCastAttribute($attribute): bool
    {
        return array_key_exists($attribute, $this->casts);
    }

    private function castDecodeValue(string $attribute, mixed $value): mixed
    {
        return match($this->casts[$attribute]) {
            'array', 'object' => unserialize($value, ['allowed_classes' => false]),
            default => $value,
        };
    }

    private function castEncodeValue(string $attribute, mixed $value): mixed
    {
        return match($this->casts[$attribute]) {
            'array', 'object' => serialize($value),
            default => $value,
        };
    }

    private function arrayToCastEncodeValue($values): array
    {
        $encoded = [];
        foreach ($values as $attribute => $value) {
            $encoded[$attribute] = $this->castEncodeValue($attribute, $value)
                ? $this->castEncodeValue($value, $attribute)
                : $value;
        }
        return $encoded;

    }



}