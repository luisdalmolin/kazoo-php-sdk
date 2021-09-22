<?php

namespace Kazoo\AuthToken;

use stdClass;
use Guzzle\Common\Event;
use Kazoo\HttpClient\HttpClient;
use Psr\Http\Message\RequestInterface;
use Kazoo\HttpClient\HttpClientInterface;
use Kazoo\Exception\AuthenticationException;
use Kazoo\HttpClient\Message\ResponseMediator;

/**
 *
 */
class User implements AuthTokenInterface {

    /**
     *
     * @var array
     */
    private $options = array(
    );

    /**
     *
     * @var null|\Kazoo\Client
     */
    private $client;

    /**
     *
     * @var string
     */
    private $username;

    /**
     *
     * @var string
     */
    private $password;

    /**
     *
     * @var string
     */
    private $sipRealm;

    /**
     *
     * @var null|stdClass
     */
    private $auth_response = null;

    /**
     *
     * @var boolean
     */
    private $disabled = false;

    /**
     *
     * @param string $username
     * @param string $password
     * @param string $sipRealm
     */
    public function __construct($username, $password, $sipRealm, $options = null) {
        @session_start();
        $this->username = $username;
        $this->password = $password;
        $this->sipRealm = $sipRealm;

        if (is_array($options)) {
            $this->options = $options;
        }
    }

    public function __destruct() {
        if (!is_null($this->auth_response)) {
            $_SESSION['Kazoo']['AuthToken']['User'][$this->username] = $this->auth_response;
        }
    }

    /**
     *
     *
     * @return null|\Kazoo\Client
     */
    public function getClient() {
        return $this->client;
    }

    /**
     *
     *
     * @param \Kazoo\Client
     */
    public function setClient(\Kazoo\Client $client) {
        $this->client = $client;
    }

    /**
     *
     * @return string
     */
    public function getAccountId() {
        return $this->getAuthResponse()->account_id;
    }

    /**
     *
     * @return string
     */
    public function getToken() {
        return $this->getAuthResponse()->auth_token;
    }

    public function reset() {
        $this->auth_response = null;
        if (!empty($_SESSION['Kazoo']['AuthToken']['User'][$this->username])) {
            unset($_SESSION['Kazoo']['AuthToken']['User'][$this->username]);
        }
    }

    /**
     *
     * @return string
     */
    private function getAuthResponse() {
        if (is_null($this->auth_response)) {
            $this->checkSessionResponse();
        }

        return $this->auth_response;
    }

    private function checkSessionResponse() {
        if (isset($_SESSION['Kazoo']['AuthToken']['User'][$this->username])) {
            $this->auth_response = $_SESSION['Kazoo']['AuthToken']['User'][$this->username];
        } else {
            $this->requestToken();
        }
    }

    private function requestToken() {
        $payload = new stdClass();
        $payload->data = new stdClass();
        $payload->data->credentials = md5($this->username . ":" . $this->password);
        $payload->data->realm = $this->sipRealm;

        $this->disabled = true;
        $tokenizedUri = $this->client->getTokenizedUri("/user_auth");
        $response = $this->client->getHttpClient()->put($tokenizedUri, json_encode($payload));
        $content = ResponseMediator::getContent($response);
        $this->disabled = false;

        switch ($content->status) {
            case "success":
                $this->auth_response = $content->data;
                $_SESSION['Kazoo']['AuthToken']['User'][$this->username] = $this->auth_response;
                $this->auth_response->auth_token = $content->auth_token;
                break;
            default:
                $message = $response->getStatusCode() . " " . $response->getReasonPhrase() . " " . $response->getProtocolVersion();
                throw new AuthenticationException($message);
        }
    }

    public function isDisabled()
    {
        return $this->disabled;
    }
}
