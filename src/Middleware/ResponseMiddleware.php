<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Middleware;

use Psr\Http\Message\ResponseInterface;

/**
 * Class ResponseMiddleware
 * @package Soatok\AnthroKit
 */
abstract class ResponseMiddleware
{
    abstract public function __invoke(
        ResponseInterface $request,
        callable $next
    ): ResponseInterface;
}
