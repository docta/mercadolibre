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
namespace Docta\MercadoLibre\Test;

use Docta\MercadoLibre\Client;
use Docta\MercadoLibre\OAuth2\Client\ResourceGeneric;
use Docta\MercadoLibre\OAuth2\Client\ResourceOwner;
use InvalidArgumentException;
use League\OAuth2\Client\Token\AccessToken;
use Mockery;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

/**
 * Represents a simplified version of MercadoLibre Provider.
 */
class ClientTest extends TestCase
{
    /**
     * @var Client
     */
    protected $Client;

    /**
     * @var string
     */
    protected $authSite = 'MLA';

    /**
     * @var string
     */
    protected $clientId = 'mockclientId';

    /**
     * @var string
     */
    protected $clientSecret = 'mockClientSecret';

    /**
     * @var string
     */
    protected $redirectUri = 'http://mockRedirectUri.com/';

    /**
     * @var string
     */
    protected $code;

    /**
     * @var string
     */
    protected $tokenType = 'bearer';

    /**
     * @var string
     */
    protected $accessToken;

    /**
     * @var string
     */
    protected $refreshToken;

    /**
     * @var string
     */
    protected $userId;

    /**
     * @var int
     */
    protected $expires;

    /**
     * @var array
     */
    protected $token;

    /**
     * @var array
     */
    protected $owner;

    /**
     * @var array
     */
    protected $header = ['content-type' => 'application/json'];

    /**
     * Setup test.
     *
     * @return void
     */
    protected function setUp()
    {
        /**
         * Setup client.
         */
        $this->Client = new Client(
            $this->authSite,
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );

        /**
         * Setup token values.
         */
        $this->code = uniqid('cod_', true);
        $this->accessToken = uniqid('atk_', true);
        $this->refreshToken = uniqid('rtk_', true);
        $this->userId = uniqid('usr_', true);
        $this->expires = time() + (6 * 60 * 60);

        /**
         * Setup token.
         */
        $this->token = [
            'token_type' => $this->tokenType,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'user_id' => $this->userId,
            'expires' => $this->expires
        ];

        /**
         * Setup owner.
         */
        $this->owner = [
            'id' => $this->userId,
            'first_name' => 'mockFirstName',
            'last_name' => 'mockLastName',
            'address' => [
                'address' => 'mockAddress',
                'country' => 'mockCountry'
            ]
        ];
    }

    /**
     * Tear down test.
     *
     * @return void
     */
    public function tearDown()
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * testConstructorWithStoredToken
     *
     * @return void
     */
    public function testConstructorWithStoredToken()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);
        $storedToken = $this->Client->setToken($this->code);

        $newAuthenticatedClient = new Client(
            $this->authSite,
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri,
            $storedToken
        );

        $token = $newAuthenticatedClient->getToken();
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame($this->accessToken, $token->getToken());
        $this->assertSame($this->refreshToken, $token->getRefreshToken());
        $this->assertFalse($token->hasExpired());
    }

    /**
     * testGetAuthorizationUrl
     *
     * @return void
     */
    public function testGetAuthorizationUrl()
    {
        $url = $this->Client->getAuthorizationUrl();
        $this->assertInternalType('string', $url);

        $url = parse_url($url);
        $this->assertArrayHasKey('scheme', $url);
        $this->assertArrayHasKey('host', $url);
        $this->assertArrayHasKey('path', $url);
        $this->assertArrayHasKey('query', $url);
        $this->assertSame('https', $url['scheme']);
        $this->assertSame('auth.mercadolibre.com.ar', $url['host']);
        $this->assertSame('/authorization', $url['path']);

        parse_str($url['query'], $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame($this->redirectUri, $query['redirect_uri']);
        $this->assertSame($this->clientId, $query['client_id']);
        $this->assertSame($this->Client->getState(), $query['state']);
    }

    /**
     * testSetTokenFromCode
     *
     * @return void
     */
    public function testSetTokenFromCode()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        $token = $this->Client->setToken($this->code);

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame($this->accessToken, $token->getToken());
        $this->assertSame($this->refreshToken, $token->getRefreshToken());
        $this->assertFalse($token->hasExpired());
    }

    /**
     * testSetTokenFromToken
     *
     * @return void
     */
    public function testSetTokenFromToken()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        $token = $this->Client->setToken($this->code);
        $token = $this->Client->setToken($token);

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame($this->accessToken, $token->getToken());
        $this->assertSame($this->refreshToken, $token->getRefreshToken());
        $this->assertFalse($token->hasExpired());
    }

    /**
     * testGetToken
     *
     * @return void
     */
    public function testGetToken()
    {
        $this->assertNull($this->Client->getToken());
    }

    /**
     * testGetTokenWithToken
     *
     * @return void
     */
    public function testGetTokenWithToken()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);
        $this->Client->setToken($this->code);

        $token = $this->Client->getToken();

        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertSame($this->accessToken, $token->getToken());
        $this->assertSame($this->refreshToken, $token->getRefreshToken());
        $this->assertFalse($token->hasExpired());
    }

    /**
     * testGetTokenWithExpiratedToken
     *
     * @return void
     */
    public function testGetTokenWithExpiratedToken()
    {
        $expiratedToken = $this->token;
        $expiratedToken['expires'] = time() - 1;

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($expiratedToken));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        $expiratedToken = $this->Client->getAccessToken('authorization_code', ['code' => $this->code]);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        $refreshedToken = $this->Client->setToken($expiratedToken);

        $this->assertInstanceOf(AccessToken::class, $refreshedToken);
        $this->assertSame($this->accessToken, $refreshedToken->getToken());
        $this->assertSame($this->refreshToken, $refreshedToken->getRefreshToken());
        $this->assertFalse($refreshedToken->hasExpired());
    }

    /**
     * testGetOwner
     *
     * @return void
     */
    public function testGetOwner()
    {
        $this->assertNull($this->Client->getOwner());
    }

    /**
     * testGetOwnerWithToken
     *
     * @return void
     */
    public function testGetOwnerWithToken()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        $this->Client->setToken($this->code);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->owner));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        $owner = $this->Client->getOwner();

        $this->assertInstanceOf(ResourceOwner::class, $owner);
        $this->assertSame($this->owner['id'], $owner->getId());
        $this->assertSame($this->owner['first_name'], $owner->get('first_name'));
        $this->assertSame($this->owner['last_name'], $owner->get('last_name'));
        $this->assertSame($this->owner['address']['address'], $owner->get('address.address'));
        $this->assertSame($this->owner['address']['country'], $owner->get('address.country'));
        $this->assertSame($this->owner, $owner->toArray());
    }

    /**
     * testRequests
     *
     * @return void
     */
    public function testRequests()
    {
        foreach (['get', 'post', 'put', 'delete', 'options'] as $method) {
            $mockResponse = ['mockResponseKey' => 'mockResponseValue'];

            $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
            $response->shouldReceive('getHeader')->andReturn($this->header);
            $response->shouldReceive('getBody')->andReturn(json_encode($mockResponse));
            $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
            $httpClient->shouldReceive('send')->times(1)->andReturn($response);
            $this->Client->setHttpClient($httpClient);

            $resource = $this->Client->{$method}('/mochPath');
            $this->assertInstanceOf(ResourceGeneric::class, $resource);
            $this->assertInternalType('array', $resource->toArray());
            $this->assertArrayHasKey('mockResponseKey', $resource->toArray());
            $this->assertContains($mockResponse['mockResponseKey'], $resource->toArray());
            $this->assertSame($mockResponse['mockResponseKey'], $resource->get('mockResponseKey'));
        }
    }

    /**
     * testBuildRequest
     *
     * @return void
     */
    public function testBuildRequest()
    {
        $url = 'https://api.mercadolibre.com/items/validate';
        $data = ['mockRequestKey' => 'mockRequestValue'];
        $request = $this->Client->buildRequest('POST', $url, $data);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https', $request->getUri()->getScheme());
        $this->assertSame('api.mercadolibre.com', $request->getUri()->getHost());
        $this->assertSame('/items/validate', $request->getUri()->getPath());
        $this->assertEmpty($request->getUri()->getQuery());
        $this->assertSame(json_encode($data), (string) $request->getBody());
        $this->assertContains('application/json', $request->getHeader('content-type'));
    }

    /**
     * testBuildRequest
     *
     * @return void
     */
    public function testBuildRequestAuthenticated()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getHeader')->andReturn($this->header);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->token));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);
        $this->Client->setToken($this->code);

        $url = 'https://api.mercadolibre.com/items/validate';
        $data = ['mockRequestKey' => 'mockRequestValue'];
        $query = ['access_token' => $this->accessToken];
        $request = $this->Client->buildRequest('POST', $url, $data);

        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertSame('POST', $request->getMethod());
        $this->assertSame('https', $request->getUri()->getScheme());
        $this->assertSame('api.mercadolibre.com', $request->getUri()->getHost());
        $this->assertSame('/items/validate', $request->getUri()->getPath());
        $this->assertSame(http_build_query($query), $request->getUri()->getQuery());
        $this->assertSame(json_encode($data), (string) $request->getBody());
        $this->assertContains('application/json', $request->getHeader('content-type'));
    }

    /**
     * testBuildResourceWithDifferentArrayResponse
     *
     * @return void
     */
    public function testBuildResourceWithDifferentArrayResponse()
    {
        $this->assertNull($this->Client->buildResource());
    }
}
