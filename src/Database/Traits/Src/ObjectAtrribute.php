<?php

namespace FimPablo\SigExtenders\Database\Traits\Src;

use ArrayAccess;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use JsonSerializable;

final class ObjectAtrribute implements Arrayable, ArrayAccess, Jsonable, JsonSerializable
{
    public function __construct(object|array $attrs = [])
    {
        foreach ($attrs as $key => $value) {
            $this->{$key} = $value;
        }
    }

    public function toArray(): array
    {
        return json_decode(json_encode($this), true);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }

    public function toJson($options = 0): string
    {
        return json_encode($this, $options);
    }

    public function jsonSerialize()
    {
        return $this;
    }

    public function collect(): Collection
    {
        return collect(get_object_vars($this));
    }
}
