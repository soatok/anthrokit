<?php
declare(strict_types=1);
namespace Soatok\AnthroKit\Exceptions;

use ParagonIE\Corner\CornerInterface;
use ParagonIE\Corner\CornerTrait;

/**
 * Class BaseException
 * @package Soatok\AnthroKit\Exceptions
 */
class BaseException extends \Exception implements CornerInterface
{
    use CornerTrait;
}
