<?php

namespace Kazoo\HttpClient;

use Monolog\Logger;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\MessageFormatter;
use Kazoo\Exception\ErrorException;
use Kazoo\Exception\RuntimeException;
use GuzzleHttp\Client as GuzzleClient;
use Kazoo\HttpClient\Middleware\ErrorMiddleware;

/**
 * Performs requests on Kazoo API.
 *
 */
class HttpClient implements HttpClientInterface {

    protected $options = array(
        'user_agent' => 'kazoo-php-sdk (http://github.com/2600hz/kazoo-php-sdk)',
        'timeout' => 10,
        'api_limit' => 5000,
        'api_version' => '1',
        'log_type' => null,
        'log_file' => null,
        'cache_dir' => null
    );
    protected $headers = array();
    private $lastResponse;
    private $lastRequest;

    /**
     * @param array           $options
     * @param ClientInterface $client
     */
    public function __construct(array $options = array(), ClientInterface $client = null)
    {
        $this->options = array_merge($this->options, $options);
        $this->options['handler']->push(new ErrorMiddleware($options));

        $logger = $this->getLogger();

        if (! is_null($logger)){
            $this->options['handler']->push(
                Middleware::log($logger, MessageFormatter::DEBUG)
            );
        }

        $client = $client ? : new GuzzleClient($this->options);
        $this->client = $client;

        $this->clearHeaders();
    }

    /**
     * @return Request
     */
    public function getLastRequest()
    {
        return $this->lastRequest;
    }

    /**
     * @return Response
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }

    /**
     * {@inheritDoc}
     */
    public function setOption($name, $value) {
        $this->options[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function setHeaders(array $headers) {
        $this->headers = array_merge($this->headers, $headers);
    }

    /**
     * Clears used headers
     */
    public function clearHeaders() {
        $this->headers = array(
            'User-Agent' => sprintf('%s', $this->options['user_agent'])
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get($path, array $parameters = array(), array $headers = array())
    {
        return $this->request($path, null, 'GET', $headers, array('query' => $parameters));
    }

    /**
     * {@inheritDoc}
     */
    public function post($path, $body = null, array $headers = array()) {
        return $this->request($path, $body, 'POST', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function patch($path, $body = null, array $headers = array()) {
        return $this->request($path, $body, 'PATCH', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($path, $body = null, array $headers = array()) {
        return $this->request($path, $body, 'DELETE', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function put($path, $body, array $headers = array()) {
        return $this->request($path, $body, 'PUT', $headers);
    }

    /**
     * {@inheritDoc}
     */
    public function request($path, $body = null, $httpMethod = 'GET', array $headers = array(), array $options = array())
    {
        try {
            $merged_headers = array_merge($this->headers, $headers);
            $response = $this->client->request($httpMethod, $path, array_merge(array_filter([
                'headers' => $merged_headers,
                'body' => is_string($body) ? $body : null,
                'json' => is_array($body) ? $body : null,
                'http_errors' => false,
            ]), $options));
        } catch (\LogicException $e) {
            throw new ErrorException($e->getMessage());
        } catch (\RuntimeException $e) {
            throw new RuntimeException($e->getMessage());
        }

        $this->lastResponse = $response;

        return $response;
    }

    protected function createRequest($httpMethod, $path, $body = null, array $headers = array(), array $options = array())
    {
        $merged_headers = array_merge($this->headers, $headers);
        return $this->client->createRequest($httpMethod, $path, $merged_headers, $body, $options);
    }

    /**
     * @return Logger|null
     */
    protected function getLogger()
    {
        $logger = null;

        switch ($this->options['log_type']){
            case "file":
                $logger = new Logger('sdk_logger');
                $logger->pushHandler(new \Monolog\Handler\StreamHandler($this->options['log_file'], LOGGER::DEBUG));
                break;
            case "stdout":
                $logger = new Logger('sdk_logger');
                $logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout', LOGGER::DEBUG));
                break;
            default:
                $logger = null;
        }

        return $logger;
    }
}
