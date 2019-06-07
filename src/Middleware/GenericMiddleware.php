<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Middleware;

use Psr\Http\Message\MessageInterface;
use Soatok\AnthroKit\Middleware;

/**
 * Class RequestMiddleware
 * @package Soatok\AnthroKit
 */
abstract class GenericMiddleware extends Middleware
{
    abstract public function __invoke(
        MessageInterface $request,
        callable $next
    ): MessageInterface;
}
