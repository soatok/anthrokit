<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use Slim\Http\Stream;

/**
 * Class Utility
 * @package Soatok\AnthroKit
 */
abstract class Utility
{
    /**
     * @param string $class
     * @return string
     */
    public static function decorateClassName($class = ''): string
    {
        return 'Object (' . \trim($class, '\\') . ')';
    }

    /**
     * Get a variable's type. If it's an object, also get the class name.
     *
     * @param mixed $mixed
     * @return string
     */
    public static function getGenericType($mixed = null): string
    {
        if (\func_num_args() === 0) {
            return 'void';
        }
        if ($mixed === null) {
            return 'null';
        }
        if (\is_object($mixed)) {
            return static::decorateClassName(\get_class($mixed));
        }
        $type = \gettype($mixed);
        switch ($type) {
            case 'boolean':
                return 'bool';
            case 'double':
                return 'float';
            case 'integer':
                return 'int';
            default:
                return $type;
        }
    }

    /**
     * @param string $body
     * @return Stream
     */
    public static function stringToStream(string $body): Stream
    {
        $resource = \fopen('php://temp', 'wb');
        \fwrite($resource, $body);
        return new Stream($resource);
    }
}
