<?php 

namespace Kazoo\HttpClient\Middleware;

use Kazoo\Exception\ErrorException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Kazoo\Exception\ApiLimitExceedException;
use Kazoo\Exception\AuthenticationException;
use Kazoo\Exception\ValidationFailedException;
use Kazoo\HttpClient\Message\ResponseMediator;

class ErrorMiddleware
{
    /** @var array */
    protected $options;

    public function __construct($options)
    {
        $this->options = $options;
    }

    public function __invoke(callable $handler)
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $promise = $handler($request, $options);
            
            return $promise->then(function (ResponseInterface $response) use ($request) {
                if ($response->getStatusCode() < 400) {
                    return $response;
                }

                $remaining = isset($response->getHeader('X-RateLimit-Remaining')[0])
                    ? $response->getHeader('X-RateLimit-Remaining')[0]
                    : null;

                if (null != $remaining && 1 > $remaining && 'rate_limit' !== substr($request->getUri()->getPath(), 1, 10)) {
                    throw new ApiLimitExceedException($this->options['api_limit']);
                }

                $content = ResponseMediator::getContent($response, true);

                if (! is_array($content) || ! isset($content['message'])) {
                    $content = array(
                        'message' => 'unknown error',
                        'errors' => array()
                    );
                }

                switch ($response->getStatusCode()) {
                    case 400:
                        throw new ErrorException($content['message'], 400);
                    case 401:
                        $message = $response->getStatusCode() . " " . $response->getReasonPhrase() . " " . $response->getProtocolVersion();
                        throw new AuthenticationException($message);
                    default:
                        $this->collectValidationErrors($content);
                }
            });
        };
    }

    private function collectValidationErrors($content)
    {
        $errors = array();
        
        if(isset($content['data'][$content['message']])) {
            $errors[] = $content['data'][$content['message']];
        } else {
            $errors[] = $content['message'];
        }

        throw new ValidationFailedException('Validation Failed: ' . implode(', ', $errors), 422);
    }
}