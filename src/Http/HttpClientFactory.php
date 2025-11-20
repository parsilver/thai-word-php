<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Http;

use Farzai\ThaiWord\Contracts\HttpClientInterface;
use Farzai\ThaiWord\Exceptions\MissingDependencyException;

/**
 * Factory for creating HTTP clients with feature detection
 *
 * Detects if PSR HTTP packages are available and creates
 * appropriate HTTP client implementations. Provides helpful
 * error messages when dependencies are missing.
 */
class HttpClientFactory
{
    /**
     * Create HTTP client using PSR-18/PSR-17 auto-discovery
     *
     * Note: Timeout configuration is handled by the underlying HTTP client
     * implementation discovered via PSR auto-discovery. To customize timeout,
     * create your own HttpClientInterface implementation or configure the
     * discovered client directly.
     *
     * @throws MissingDependencyException If HTTP client dependencies not available
     */
    public static function create(): HttpClientInterface
    {
        // Check if HTTP discovery is available
        if (! self::isHttpClientAvailable()) {
            throw MissingDependencyException::forHttpClient();
        }

        // Use PSR auto-discovery to find implementations
        $httpClient = \Http\Discovery\Psr18ClientDiscovery::find();
        $requestFactory = \Http\Discovery\Psr17FactoryDiscovery::findRequestFactory();

        return new Psr18HttpClientAdapter($httpClient, $requestFactory);
    }

    /**
     * Create HTTP client if available, return null otherwise
     */
    public static function createIfAvailable(): ?HttpClientInterface
    {
        if (! self::isHttpClientAvailable()) {
            return null;
        }

        try {
            return self::create();
        } catch (MissingDependencyException) {
            return null;
        }
    }

    /**
     * Check if HTTP client dependencies are available
     *
     * @return bool True if PSR HTTP packages are available
     */
    public static function isHttpClientAvailable(): bool
    {
        // Check for required classes
        $requiredClasses = [
            'Psr\Http\Client\ClientInterface',
            'Psr\Http\Message\RequestFactoryInterface',
            'Http\Discovery\Psr18ClientDiscovery',
            'Http\Discovery\Psr17FactoryDiscovery',
        ];

        foreach ($requiredClasses as $class) {
            if (! interface_exists($class) && ! class_exists($class)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of missing dependencies
     *
     * @return array<string> List of missing package names
     */
    public static function getMissingDependencies(): array
    {
        $dependencies = [
            'Psr\Http\Client\ClientInterface' => 'psr/http-client',
            'Psr\Http\Message\RequestFactoryInterface' => 'psr/http-factory',
            'Http\Discovery\Psr18ClientDiscovery' => 'php-http/discovery',
        ];

        $missing = [];

        foreach ($dependencies as $class => $package) {
            if (! interface_exists($class) && ! class_exists($class)) {
                $missing[] = $package;
            }
        }

        return $missing;
    }
}
