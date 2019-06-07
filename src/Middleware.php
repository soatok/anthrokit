<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use Slim\Container;

/**
 * Class Middleware
 */
abstract class Middleware
{
    /** @var Container $container */
    protected $container;

    /**
     * Middleware constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}
