<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use Interop\Container\Exception\ContainerException;
use ParagonIE\CSPBuilder\CSPBuilder;
use Psr\Http\Message\{
    RequestInterface,
    ResponseInterface
};
use Slim\Container;
use Slim\Http\{
    Headers,
    Response,
    StatusCode,
    Stream
};
use Twig\Environment;
use Twig\Error\{
    LoaderError,
    RuntimeError,
    SyntaxError
};

/**
 * Class Endpoint
 * @package Soatok\AnthroKit
 */
abstract class Endpoint
{
    const TYPE_FORM = 'form-data';
    const TYPE_JSON = 'json';

    /** @var Container $container */
    protected $container;

    /** @var CSPBuilder $cspBuilder */
    protected $cspBuilder;

    /** @var array<string, Splice> $splices */
    protected $splices = [];

    /**
     * Endpoint constructor.
     * @param Container $container
     * @throws ContainerException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $cspBuilder = $this->container->get('csp');
        if (!$cspBuilder instanceof CSPBuilder) {
            throw new \TypeError(
                'Container must contain an instance of CSPBuilder at key "csp".'
            );
        }
        $this->cspBuilder = $cspBuilder;
    }

    /**
     * @return string
     */
    public function getDefaultSpliceNamespace(): string
    {
        $nsPath = explode('\\', get_class($this));
        array_pop($nsPath);
        array_pop($nsPath);
        $nsPath[] = 'Splices';
        return implode('\\', $nsPath);
    }

    /**
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function json(
        array $data,
        int $status = StatusCode::HTTP_OK,
        array $headers = []
    ): Response {
        $headers['Content-Type'] = 'application/json';
        return $this->respond(
            json_encode($data, JSON_PRETTY_PRINT),
            $status,
            $headers
        );
    }

    /**
     * @param RequestInterface $req
     * @param string $type
     * @return array
     */
    public function getPostBody(RequestInterface $req, $type = self::TYPE_FORM): array
    {
        $params = $req->getBody();
        switch ($type) {
            case self::TYPE_FORM:
                $arr = [];
                parse_str($params, $arr);
                return $arr;

            case self::TYPE_JSON:
                $parsed = json_decode($params, true);
                if (!$parsed) {
                    return [];
                }
                return $parsed;

            default:
                return [];
        }
    }

    /**
     * @param string $file
     * @param array $args
     * @return string
     *
     * @throws ContainerException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $file, array $args = []): string
    {
        /** @var Environment $twig */
        $twig = $this->container->get('twig');
        return $twig->render($file, $args);
    }

    /**
     * Synthesize an HTTP response
     *
     * @param string $body
     * @param int $status
     * @param array $headers
     * @return Response
     */
    public function respond(
        string $body,
        int $status = StatusCode::HTTP_OK,
        array $headers = []
    ): Response {
        return $this->cspBuilder->injectCSPHeader(
            new Response(
                $status,
                new Headers($headers),
                $this->stream($body)
            )
        );
    }

    /**
     * @param string $name
     * @param string|null $namespace
     * @return Splice
     */
    public function splice(string $name, ?string $namespace = null): Splice
    {
        if (!isset($this->splices[$name])) {
            if (!$namespace) {
                $namespace = $this->getDefaultSpliceNamespace();
            }
            $className = trim($namespace, '\\') . '\\' . $name;
            $this->splices[$name] = new $className($this->container);
        }
        return $this->splices[$name];
    }

    /**
     * Create Stream object from string
     *
     * @param string $input
     * @return Stream
     */
    public function stream(string $input)
    {
        $fp = fopen('php://temp', 'wb');
        fwrite($fp, $input);
        return new Stream($fp);
    }

    /**
     * @param string $file
     * @param array $args
     * @param int $status
     * @param array $headers
     * @return Response
     * @throws ContainerException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(
        string $file,
        array $args = [],
        int $status = StatusCode::HTTP_OK,
        array $headers = []
    ): Response {
        $headers['Content-Type'] = 'text/html';
        return $this->respond(
            $this->render($file, $args),
            $status,
            $headers
        );
    }

    /**
     * @param RequestInterface $request
     * @param ResponseInterface|null $response
     * @param array $routerParams
     * @return ResponseInterface
     */
    public abstract function __invoke(
        RequestInterface $request,
        ?ResponseInterface $response = null,
        array $routerParams = []
    ): ResponseInterface;
}
