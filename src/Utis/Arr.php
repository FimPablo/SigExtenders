<?php

namespace FimPablo\SigExtenders\Utils;

use Illuminate\Support\Arr as LaravelArr;

class Arr extends LaravelArr
{
    public static function without(array $array, array|float|int|string $keys)
    {
        self::forget($array, $keys);

        return $array;
    }

    public static function renameKeys(array $array, array $keys)
    {
        $formatedArray = [];

        foreach ($array as $key => $value) {
            if (self::get($keys, $key) !== null) {
                $key = $keys[$key];
            }
            $formatedArray[$key] = $value;
        }

        return $formatedArray;
    }

    public static function just(array $array, string|int|array $key)
    {
        $returnArray = [];

        if (!is_array($key)) {
            $key = [$key];
        }

        if (in_array('*', $key)) {
            return $array;
        }

        foreach ($key as $k) {
            if (!in_array($k, array_keys($array)))
                continue;

            $returnArray[$k] = Arr::get($array, $k);
        }

        return $returnArray;
    }

    public static function hasKeys(array $array, array|string $keys)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }

        $keysFinded = array_intersect($keys, array_keys($array));

        return count($keysFinded) === count($keys);
    }

    public static function each(array $array, callable $callback)
    {
        foreach ($array as $key => $value) {
            $callback($value, $key);
        }
    }

    public static function dotMap(array $array, callable $callback)
    {
        $array = self::dot($array);
        Arr::map($array, fn($value) => $callback($value));
        return self::undot($array);
    }
}
