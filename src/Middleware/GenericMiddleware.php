<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Middleware;

use Psr\Http\Message\MessageInterface;

/**
 * Class RequestMiddleware
 * @package Soatok\AnthroKit
 */
abstract class GenericMiddleware
{
    abstract public function __invoke(
        MessageInterface $request,
        callable $next
    ): MessageInterface;
}
