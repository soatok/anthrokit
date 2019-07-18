<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use GuzzleHttp\Client;
use ParagonIE\Certainty\RemoteFetch;
use ParagonIE\Certainty\Exception\CertaintyException;
use Slim\Http\Stream;

/**
 * Class Utility
 * @package Soatok\AnthroKit
 */
abstract class Utility
{
    /**
     * Get an HTTP client (Guzzle) hardened to use TLSv1.2 and the
     * latest CACert bundle.
     *
     * @param string $certDir
     * @return Client
     * @throws CertaintyException
     * @throws \SodiumException
     */
    public static function getHttpClient(string $certDir): Client
    {
        return new Client([
            'curl.options' => [
                // https://github.com/curl/curl/blob/6aa86c493bd77b70d1f5018e102bc3094290d588/include/curl/curl.h#L1927
                CURLOPT_SSLVERSION =>
                    CURL_SSLVERSION_TLSv1_2 | (CURL_SSLVERSION_TLSv1 << 16)
            ],
            'verify' => (new RemoteFetch($certDir))
                ->getLatestBundle()
                ->getFilePath()
        ]);
    }

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
