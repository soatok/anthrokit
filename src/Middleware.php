<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use Psr\Http\Message\{
    MessageInterface,
    RequestInterface,
    ResponseInterface
};
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

    /**
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param callable $next
     * @return MessageInterface
     */
    abstract public function __invoke(
        RequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): MessageInterface;
}
