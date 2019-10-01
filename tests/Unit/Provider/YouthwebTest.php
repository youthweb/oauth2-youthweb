<?php
/*
 * A PHP Library to simplify the OAuth2 process with youthweb.net.
 * Copyright (C) 2016-2019  Youthweb e.V. <info@youthweb.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Youthweb\OAuth2\Client\Tests\Unit\Provider;

class YouthwebTest extends \PHPUnit\Framework\TestCase
{
    protected $provider;

    protected function setUp(): void
    {
        $this->provider = new \Youthweb\OAuth2\Client\Provider\Youthweb([
            'clientId' => 'mock_client_id',
            'clientSecret' => 'mock_secret',
            'redirectUri' => 'none',
            'apiVersion' => '0.1',
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

        $this->assertStringContainsString(urlencode(implode(',', $options['scope'])), $url);
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
        $userId = rand(1000, 99999);
        $firstname = uniqid();
        $lastname = uniqid();
        $nickname = uniqid();
        $email = uniqid();

        $postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
        $postResponse->method('getBody')->willReturn('access_token=mock_access_token&expires=3600&refresh_token=mock_refresh_token&otherKey={1234}');
        $postResponse->method('getHeader')->willReturn(['content-type' => 'application/x-www-form-urlencoded']);
        $postResponse->method('getStatusCode')->willReturn(200);

        $userResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
        $userResponse->method('getBody')->willReturn('{"data": {"type": "users","id": "' . $userId . '","attributes": {"username": "' . $nickname . '","first_name": "' . $firstname . '","last_name": "' . $lastname . '","email": "' . $email . '","birthday": "1988-03-05","created_at": "2006-01-01T21:00:00+01:00","last_login": "2016-01-01T22:00:00+02:00","zip": "12345","city": "Jamestown","description_jesus": "Lorem ipsum dolor sit amet","description_job": "Lorem ipsum dolor sit amet","description_hobbies": "Lorem ipsum dolor sit amet","description_motto": "Lorem ipsum dolor sit amet","picture_thumb_url": "https://youthweb.net/img/steckbriefe/default_pic_m.jpg","picture_url": "https://youthweb.net/img/steckbriefe/default_pic_m.jpg"}}}');
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
        $this->assertEquals($userId, $user->toArray()['data']['id']);
        $this->assertEquals($firstname . ' ' . $lastname, $user->getName());
        $this->assertEquals($firstname . ' ' . $lastname, $user->toArray()['data']['attributes']['first_name'] . ' ' . $user->toArray()['data']['attributes']['last_name']);
        $this->assertEquals($nickname, $user->getNickname());
        $this->assertEquals($nickname, $user->toArray()['data']['attributes']['username']);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($email, $user->toArray()['data']['attributes']['email']);
        $this->assertStringContainsString((string) $userId, $user->getUrl());
    }

    /**
     * @test errors
     **/
    public function testExceptionThrownWhenErrorObjectWithDetailReceived()
    {
        $status = rand(400, 600);
        $postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
        $postResponse->method('getBody')->willReturn('{"errors": [{"status": "406","title": "Not Acceptable","detail": "You havn\'t specified the the Accept Header. You have to use Accept application/vnd.api+json, application/vnd.api+json; net.youthweb.api.version="}]}');
        $postResponse->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponse->method('getStatusCode')->willReturn($status);

        $client = $this->createMock('GuzzleHttp\ClientInterface');
        $client->expects($this->once())
            ->method('send')
            ->willReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException('League\OAuth2\Client\Provider\Exception\IdentityProviderException');
        $this->expectExceptionMessage('You havn\'t specified the the Accept Header. You have to use Accept application/vnd.api+json, application/vnd.api+json; net.youthweb.api.version=');

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @test errors
     **/
    public function testExceptionThrownWhenErrorObjectWithoutDetailReceived()
    {
        $status = rand(400, 600);
        $postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
        $postResponse->method('getBody')->willReturn('{"errors": [{"status": "406","title": "Not Acceptable"}]}');
        $postResponse->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponse->method('getStatusCode')->willReturn($status);

        $client = $this->createMock('GuzzleHttp\ClientInterface');
        $client->expects($this->once())
            ->method('send')
            ->willReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException('League\OAuth2\Client\Provider\Exception\IdentityProviderException');
        $this->expectExceptionMessage('Not Acceptable');

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }

    /**
     * @test errors
     **/
    public function testExceptionThrownWhenOAuthErrorReceived()
    {
        $status = rand(400, 600);
        $postResponse = $this->createMock('Psr\Http\Message\ResponseInterface');
        $postResponse->method('getBody')->willReturn('{"error": "invalid_request","error_description": "The request is missing a required parameter, includes an unsupported parameter value (other than grant type), repeats a parameter, includes multiple credentials, utilizes more than one mechanism for authenticating the client, or is otherwise malformed.","error_uri": "https://tools.ietf.org/html/rfc6749#section-5.2"}');
        $postResponse->method('getHeader')->willReturn(['content-type' => 'json']);
        $postResponse->method('getStatusCode')->willReturn($status);

        $client = $this->createMock('GuzzleHttp\ClientInterface');
        $client->expects($this->once())
            ->method('send')
            ->willReturn($postResponse);
        $this->provider->setHttpClient($client);

        $this->expectException('League\OAuth2\Client\Provider\Exception\IdentityProviderException');
        $this->expectExceptionMessage('invalid_request');

        $token = $this->provider->getAccessToken('authorization_code', ['code' => 'mock_authorization_code']);
    }
}
