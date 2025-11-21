<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Exceptions;

/**
 * Exception thrown when required dependencies are not installed
 *
 * This exception provides helpful error messages with installation instructions
 * for optional dependencies (e.g., HTTP client packages for remote dictionaries).
 */
class MissingDependencyException extends SegmentationException
{
    /**
     * Create exception for missing HTTP client dependencies
     */
    public static function forHttpClient(): self
    {
        return new self(
            "HTTP transport library is required for downloading remote dictionaries.\n".
            "Please install the required package:\n\n".
            "  composer require farzai/transport\n\n".
            'Alternatively, use local dictionary files to avoid this dependency.',
            self::CONFIG_MISSING_REQUIRED
        );
    }

    /**
     * Create exception for missing specific class
     *
     * @param  string  $className  The missing class name
     * @param  string  $package  The package that provides the class
     */
    public static function forClass(string $className, string $package): self
    {
        return new self(
            "Required class '{$className}' is not available.\n".
            "Please install: composer require {$package}",
            self::CONFIG_MISSING_REQUIRED
        );
    }
}
