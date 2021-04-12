<?php

namespace Softwarepunt\Instarecord\Models;

/**
 * Interface marker for read-only / immutable models:
 * Models that implement this interface will reject changes operations like save(), create(), update(), delete(), etc.
 */
interface IReadOnlyModel
{
    // No implementation
}