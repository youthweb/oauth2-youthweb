<?php

namespace Youthweb\OAuth2\Client\Test\Provider;

class YouthwebTest extends \PHPUnit_Framework_TestCase
{
	protected $provider;

	protected function setUp()
	{
		$this->provider = new \Youthweb\OAuth2\Client\Provider\Youthweb([
			'clientId' => 'mock_client_id',
			'clientSecret' => 'mock_secret',
			'redirectUri' => 'none',
		]);
	}

	public function testAuthorizationUrl()
	{
		$url = $this->provider->getAuthorizationUrl();
		$uri = parse_url($url);
		parse_str($uri['query'], $query);

		$this->assertArrayHasKey('client_id', $query);
		$this->assertArrayHasKey('redirect_uri', $query);
		$this->assertArrayHasKey('state', $query);
		$this->assertArrayHasKey('scope', $query);
		$this->assertArrayHasKey('response_type', $query);
		$this->assertArrayHasKey('approval_prompt', $query);
		$this->assertNotNull($this->provider->getState());
	}

	public function testScopes()
	{
		$options = ['scope' => [uniqid(),uniqid()]];

		$url = $this->provider->getAuthorizationUrl($options);

		$this->assertContains(urlencode(implode(',', $options['scope'])), $url);
	}

	public function testGetAuthorizationUrl()
	{
		$url = $this->provider->getAuthorizationUrl();
		$uri = parse_url($url);

		$this->assertEquals('/auth/authorize', $uri['path']);
	}

	public function testGetBaseAccessTokenUrl()
	{
		$params = [];

		$url = $this->provider->getBaseAccessTokenUrl($params);
		$uri = parse_url($url);

		$this->assertEquals('/auth/access_token', $uri['path']);
	}

	public function testGetAccessToken()
	{
		$response = $this->createMock('Psr\Http\Message\ResponseInterface');
		$response->method('getBody')->willReturn('{"access_token":"mock_access_token", "scope":"user:read", "token_type":"bearer"}');
		$response->method('getHeader')->willReturn(['content-type' => 'json']);
		$response->method('getStatusCode')->willReturn(200);

		$client = $this->createMock('GuzzleHttp\ClientInterface');
		$client->expects($this->once())->method('send')->willReturn($response);
		$this->provider->setHttpClient($client);

		$token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);

		$this->assertEquals('mock_access_token', $token->getToken());
		$this->assertNull($token->getExpires());
		$this->assertNull($token->getRefreshToken());
		$this->assertNull($token->getResourceOwnerId());
	}

	public function testUserData()
	{
		$userId = rand(1000,9999);
		$name = uniqid();
		$nickname = uniqid();
		$email = uniqid();

		$postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
		$postResponse->method('getBody')->willReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
		$postResponse->method('getHeader')->willReturn(['content-type' => 'application/x-www-form-urlencoded']);
		$postResponse->method('getStatusCode')->willReturn(200);

		$userResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
		$userResponse->method('getBody')->willReturn('{"id": '.$userId.', "login": "'.$nickname.'", "name": "'.$name.'", "email": "'.$email.'"}');
		$userResponse->method('getHeader')->willReturn(['content-type' => 'json']);
		$userResponse->method('getStatusCode')->willReturn(200);

		$client = $this->createMock('GuzzleHttp\ClientInterface');
		$client->expects($this->exactly(2))
			->method('send')
			->willReturn($postResponse, $userResponse);
		$this->provider->setHttpClient($client);

		$token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
		$user = $this->provider->getResourceOwner($token);

		$this->assertEquals($userId, $user->getId());
		$this->assertEquals($userId, $user->toArray()['id']);
		$this->assertEquals($name, $user->getName());
		$this->assertEquals($name, $user->toArray()['name']);
		$this->assertEquals($nickname, $user->getNickname());
		$this->assertEquals($nickname, $user->toArray()['login']);
		$this->assertEquals($email, $user->getEmail());
		$this->assertEquals($email, $user->toArray()['email']);
		$this->assertContains($nickname, $user->getUrl());
	}

	/**
	 * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
	 **/
	public function testExceptionThrownWhenErrorObjectReceived()
	{
		$status = rand(400,600);
		$postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
		$postResponse->method('getBody')->willReturn('{"message": "Validation Failed","errors": [{"resource": "Issue","field": "title","code": "missing_field"}]}');
		$postResponse->method('getHeader')->willReturn(['content-type' => 'json']);
		$postResponse->method('getStatusCode')->willReturn($status);

		$client = $this->createMock('GuzzleHttp\ClientInterface');
		$client->expects($this->once())
			->method('send')
			->willReturn($postResponse);
		$this->provider->setHttpClient($client);
		$token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
	}

	/**
	 * @expectedException League\OAuth2\Client\Provider\Exception\IdentityProviderException
	 **/
	public function testExceptionThrownWhenOAuthErrorReceived()
	{
		$status = 200;
		$postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
		$postResponse->method('getBody')->willReturn('{"error": "bad_verification_code","error_description": "The code passed is incorrect or expired.","error_uri": "https://developer.github.com/v3/oauth/#bad-verification-code"}');
		$postResponse->method('getHeader')->willReturn(['content-type' => 'json']);
		$postResponse->method('getStatusCode')->willReturn($status);

		$client = $this->createMock('GuzzleHttp\ClientInterface');
		$client->expects($this->once())
			->method('send')
			->willReturn($postResponse);
		$this->provider->setHttpClient($client);
		$token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
	}
}
