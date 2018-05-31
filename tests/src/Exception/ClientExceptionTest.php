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
namespace Docta\MercadoLibre\Test\Exception;

use Docta\MercadoLibre\Client;
use Docta\MercadoLibre\Exception\ClientException;
use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * Represents an exception for the client.
 */
class ClientExceptionTest extends TestCase
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
     * @var array
     */
    protected $error = [
        'message' => 'mockMessage',
        'error' => 'mockError',
        'status' => 400,
        'cause' => []
    ];

    /**
     * Setup test.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->Client = new Client(
            $this->authSite,
            $this->clientId,
            $this->clientSecret,
            $this->redirectUri
        );
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
     * testCheckResponseException
     *
     * @return void
     */
    public function testException()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')->andReturn($this->error['status']);
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->error));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);

        $this->Client->setHttpClient($httpClient);
        $this->expectException(ClientException::class);
        $this->expectExceptionMessage($this->error['error']);
        $this->expectExceptionCode($this->error['status']);
        $this->Client->setToken('mockCode');
    }

    /**
     * testTryException
     *
     * @return void
     */
    public function testTryException()
    {
        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')->andReturn($this->error['status']);
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->error));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        try {
            $this->Client->setToken('mockCode');
        } catch (ClientException $e) {
            $this->assertSame($this->error['message'], $e->getError());
            $this->assertSame($this->error['error'], $e->getMessage());
            $this->assertSame($this->error['status'], $e->getCode());
            $this->assertSame($this->error['cause'], $e->getCause());
            $this->assertSame($this->error, $e->getResponseBody());
        }
    }

    /**
     * testTryExceptionWithoutError
     *
     * @return void
     */
    public function testTryExceptionWithoutError()
    {
        unset($this->error['message']);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')->andReturn($this->error['status']);
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->error));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        try {
            $this->Client->setToken('mockCode');
        } catch (ClientException $e) {
            $this->assertNull($e->getError());
        }
    }

    /**
     * testTryExceptionWithoutCause
     *
     * @return void
     */
    public function testTryExceptionWithoutCause()
    {
        unset($this->error['cause']);

        $response = Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('getStatusCode')->andReturn($this->error['status']);
        $response->shouldReceive('getHeader')->andReturn(['content-type' => 'application/json']);
        $response->shouldReceive('getBody')->andReturn(json_encode($this->error));
        $httpClient = Mockery::mock('GuzzleHttp\ClientInterface');
        $httpClient->shouldReceive('send')->times(1)->andReturn($response);
        $this->Client->setHttpClient($httpClient);

        try {
            $this->Client->setToken('mockCode');
        } catch (ClientException $e) {
            $this->assertNull($e->getCause());
        }
    }
}
