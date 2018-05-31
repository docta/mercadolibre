<?php
/**
 * MercadoLibre PHP SDK
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2018 Lucas Banegas <lucasconobanegas@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @author Lucas Banegas <lucasconobanegas@gmail.com>
 * @link https://github.com/docta/mercadolibre Repository
 * @link https://docta.github.io/mercadolibre Documentation
 */
namespace Docta\MercadoLibre;

use Docta\MercadoLibre\Exception\ClientException;
use Docta\MercadoLibre\OAuth2\Client\Provider;
use Docta\MercadoLibre\OAuth2\Client\ResourceGeneric;
use Docta\MercadoLibre\OAuth2\Client\ResourceOwner;
use InvalidArgumentException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents a simplified version of MercadoLibre Provider.
 */
class Client extends Provider
{
    /**
     * @var AccessToken
     */
    protected $token;

    /**
     * @var ResourceOwner
     */
    protected $owner;

    /**
     * Constructor.
     *
     * @param string $authSite The site where the application was registered (ex: `MLA`)
     * @param string $clientId The App ID granted by MercadoLibre
     * @param string $clientSecret The secret key granted by MercadoLibre
     * @param string $redirectUri The URI that will process the MercadoLibre callback
     * @param AccessToken|string $token Code or token previously granted (optional)
     * @throws InvalidArgumentException
     * @throws ClientException
     */
    public function __construct(
        $authSite,
        $clientId,
        $clientSecret,
        $redirectUri,
        $token = null
    ) {
        parent::__construct(compact(
            'authSite',
            'clientId',
            'clientSecret',
            'redirectUri'
        ));

        if ($token) {
            $this->setToken($token);
        }
    }

    /**
     * Builds and returns the authorization URL.
     *
     * @param array $options
     * @return string Authorization URL
     */
    public function getAuthorizationUrl(array $options = [])
    {
        return parent::getAuthorizationUrl($options);
    }

    /**
     * Returns the current value of the state parameter.
     *
     * @return string
     */
    public function getState()
    {
        return parent::getState();
    }

    /**
     * Set a token using a code or a previously created token.
     *
     * @param AccessToken|string $token Code or token previously granted
     * @throws ClientException
     * @return AccessToken|null
     */
    public function setToken($token)
    {
        $this->token = $token instanceof AccessToken
            ? $token : $this->getAccessToken('authorization_code', ['code' => $token]);

        return $this->getToken();
    }

    /**
     * Get the current token.
     *
     * @throws ClientException
     * @return AccessToken|null
     */
    public function getToken()
    {
        if ($this->token && $this->token->hasExpired() && $this->token->getRefreshToken()) {
            $options = ['refresh_token' => $this->token->getRefreshToken()];
            $this->token = $this->getAccessToken('refresh_token', $options);
        }

        return $this->token;
    }

    /**
     * Get the owner resource for the current token.
     *
     * @throws ClientException
     * @return ResourceOwner|null
     */
    public function getOwner()
    {
        if ($this->getToken()) {
            $this->owner = $this->getResourceOwner($this->getToken());
        }

        return $this->owner;
    }

    /**
     * Execute a GET request
     *
     * @param string $path Path relative to the API URL
     * @param array $query Query parameters to include in the URL (optional)
     * @throws ClientException
     * @return ResourceGeneric The resource obtained by the request
     */
    public function get($path, array $query = [])
    {
        return $this->execute('GET', $path, $query);
    }

    /**
     * Execute a POST request
     *
     * @param string $path Path relative to the API URL
     * @param array $data Data to be sent through the HTTP headers (optional)
     * @param array $query Query parameters to include in the URL (optional)
     * @throws ClientException
     * @return ResourceGeneric The resource obtained by the request
     */
    public function post($path, array $data = [], array $query = [])
    {
        return $this->execute('POST', $path, $query, $data);
    }

    /**
     * Execute a PUT request
     *
     * @param string $path Path relative to the API URL
     * @param array $data Data to be sent through the HTTP headers (optional)
     * @param array $query Query parameters to include in the URL (optional)
     * @throws ClientException
     * @return ResourceGeneric The resource obtained by the request
     */
    public function put($path, array $data = [], array $query = [])
    {
        return $this->execute('PUT', $path, $query, $data);
    }

    /**
     * Execute a DELETE request
     *
     * @param string $path Path relative to the API URL
     * @param array $query Query parameters to include in the URL (optional)
     * @throws ClientException
     * @return ResourceGeneric The resource obtained by the request
     */
    public function delete($path, array $query = [])
    {
        return $this->execute('DELETE', $path, $query);
    }

    /**
     * Execute a OPTIONS request
     *
     * @param string $path Path relative to the API URL
     * @param array $query Query parameters to include in the URL (optional)
     * @throws ClientException
     * @return ResourceGeneric The resource obtained by the request
     */
    public function options($path, array $query = [])
    {
        return $this->execute('OPTIONS', $path, $query);
    }

    /**
     * Execute a custom request
     *
     * @param string $method Request method
     * @param string $path Path relative to the API URL
     * @param array $query Query parameters to include in the URL (optional)
     * @param array $data Data to be sent through the HTTP headers (optional)
     * @throws ClientException
     * @return ResourceGeneric The resource obtained by the request
     */
    public function execute($method, $path, array $query = [], array $data = [])
    {
        $url = $this->getApiUrl($path, $query);
        $request = $this->buildRequest($method, $url, $data);
        $response = $this->getParsedResponse($request);
        return $this->buildResource($response);
    }

    /**
     * Build and return an appropriate request.
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return RequestInterface
     */
    public function buildRequest($method, $url, array $data = [])
    {
        $options = empty($data) ? $data : [
            'headers' => ['content-type' => 'application/json'],
            'body' => json_encode($data)
        ];

        return empty($this->getToken())
            ? $this->getRequest($method, $url, $options)
            : $this->getAuthenticatedRequest($method, $url, $this->getToken(), $options);
    }

    /**
     * Build and return a resource.
     *
     * @param mixed $response
     * @return ResourceGeneric|mixed
     */
    public function buildResource($response = null)
    {
        return is_array($response) ? new ResourceGeneric($response) : $response;
    }

    /**
     * Checks a provider response for errors.
     *
     * @throws ClientException
     * @param ResponseInterface $response
     * @param array|string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new ClientException((string) $data['error'], (int) $data['status'], $data);
        }
    }
}
