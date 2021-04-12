<?php

namespace Softwarepunt\Instarecord\Serialization;

/**
 * Interface for objects that can be serialized as a database string.
 * Implementors must have a constructor that accepts no arguments or null arguments.
 */
interface IDatabaseSerializable
{
    /**
     * Converts this object into a string for database storage.
     *
     * @return string Serialized representation of this object.
     */
    public function dbSerialize(): string;

    /**
     * Fills this object from a string that was stored in the database.
     *
     * @param string $storedValue Serialized representation of this object.
     */
    public function dbUnserialize(string $storedValue): void;
}