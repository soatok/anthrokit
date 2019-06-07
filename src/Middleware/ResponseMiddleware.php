<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Soatok\AnthroKit\Middleware;

/**
 * Class ResponseMiddleware
 * @package Soatok\AnthroKit
 */
abstract class ResponseMiddleware extends Middleware
{
    abstract public function __invoke(
        ResponseInterface $request,
        callable $next
    ): ResponseInterface;
}
