<?php
declare(strict_types=1);
namespace Soatok\AnthroKit;

use Interop\Container\Exception\ContainerException;
use ParagonIE\ConstantTime\Base64UrlSafe;
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
    const CSRF_FORM_INDEX = 'csrf-protect';

    const TYPE_FORM = 'form-data';
    const TYPE_JSON = 'json';

    /** @var string[] $allowedRedirectDomains */
    protected $allowedRedirectDomains = [];

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
     * @throws \Exception
     */
    public function getCsrfToken(): string
    {
        if (empty($_SESSION['anti-csrf'])) {
            $_SESSION['anti-csrf'] = random_bytes(33);
        }
        return Base64UrlSafe::encode($_SESSION['anti-csrf']);
    }

    /**
     * @param array $postData
     * @return bool
     */
    public function checkCsrfToken(array $postData = []): bool
    {
        if (empty($_SESSION['anti-csrf'])) {
            return false;
        }
        if (empty($postData[static::CSRF_FORM_INDEX])) {
            return false;
        }
        return hash_equals(
            Base64UrlSafe::encode($_SESSION['anti-csrf']),
            $postData[static::CSRF_FORM_INDEX]
        );
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
    public function post(RequestInterface $req, $type = self::TYPE_FORM): array
    {
        $post = $this->getPostBody($req, $type);
        if (!$this->checkCsrfToken($post)) {
            return [];
        }
        unset($post[static::CSRF_FORM_INDEX]);
        return $post;
    }

    /**
     * @param RequestInterface $req
     * @param string $type
     * @return array
     */
    public function getPostBody(RequestInterface $req, $type = self::TYPE_FORM): array
    {
        $params = $req->getBody()->getContents();
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
     * @param string $url
     * @param int $status
     * @param bool $safe
     * @return Response
     */
    public function redirect(
        string $url,
        int $status = StatusCode::HTTP_FOUND,
        bool $safe = false
    ): Response {
        if (!$safe) {
            $parsed = parse_url($url);
            if (!empty($parsed['host'])) {
                // A domain was specified.
                if (!in_array($parsed['host'], $this->allowedRedirectDomains, true)) {
                    // Fail closed by default; prevent open redirects.
                    $url = '/';
                }
            }
        }

        return $this->cspBuilder->injectCSPHeader(
            new Response(
                $status,
                new Headers([
                    'Location' => $url
                ])
            )
        );
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
            /** @var Splice $splice */
            $splice = new $className($this->container);
            $this->splices[$name] = $splice;
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
