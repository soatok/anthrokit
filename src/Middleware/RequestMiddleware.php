<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Middleware;

use Psr\Http\Message\RequestInterface;

/**
 * Class RequestMiddleware
 * @package Soatok\AnthroKit
 */
abstract class RequestMiddleware
{
    abstract public function __invoke(
        RequestInterface $request,
        callable $next
    ): RequestInterface;
}
