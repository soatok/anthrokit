<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Middleware;

use Psr\Http\Message\RequestInterface;
use Soatok\AnthroKit\Middleware;

/**
 * Class RequestMiddleware
 * @package Soatok\AnthroKit
 */
abstract class RequestMiddleware extends Middleware
{
    abstract public function __invoke(
        RequestInterface $request,
        callable $next
    ): RequestInterface;
}
