<?php

namespace Instasell\Instarecord\Tests\Samples;

use Instasell\Instarecord\Serialization\IDatabaseSerializable;

class DummySerializableType implements IDatabaseSerializable
{
    private string $value = "";

    public function __construct(?string $value = null)
    {
        $this->setValue($value);
    }

    public function setValue(?string $value): void
    {
        $this->value = $value ?? "";
    }

    public function dbSerialize(): string
    {
        return $this->value;
    }

    public function dbUnserialize(string $storedValue): void
    {
        $this->value = $storedValue;
    }
}