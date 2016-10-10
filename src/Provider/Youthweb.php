<?php

namespace Youthweb\OAuth2\Client\Provider;

use Youthweb\OAuth2\Client\Provider\Exception\YouthwebIdentityProviderException;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Youthweb extends AbstractProvider
{
	use BearerAuthorizationTrait;

	/**
	 * Domain
	 *
	 * @var string
	 */
	public $domain = 'https://youthweb.net';

	/**
	 * Api domain
	 *
	 * @var string
	 */
	public $apiDomain = 'https://api.youthweb.net';

	/**
	 * Get authorization url to begin OAuth flow
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl()
	{
		return $this->domain.'/auth/authorize';
	}

	/**
	 * Get access token url to retrieve token
	 *
	 * @param  array $params
	 *
	 * @return string
	 */
	public function getBaseAccessTokenUrl(array $params)
	{
		return $this->domain.'/auth/access_token';
	}

	/**
	 * Get provider url to fetch user details
	 *
	 * @param  AccessToken $token
	 *
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl(AccessToken $token)
	{
		return $this->apiDomain.'/me';
	}

	/**
	 * Returns an authenticated PSR-7 request instance for Youthweb-API.
	 *
	 * @param  string $method
	 * @param  string $url
	 * @param  AccessToken|string $token
	 * @param  array $options Any of "headers", "body", and "protocolVersion".
	 * @return RequestInterface
	 */
	public function getAuthenticatedRequest($method, $url, $token, array $options = [])
	{
		$options['headers']['Accept'] = 'application/vnd.api+json, application/vnd.api+json; net.youthweb.api.version=0.5';
		$options['headers']['Content-Type'] = 'application/vnd.api+json';

		return parent::getAuthenticatedRequest($method, $url, $token, $options);
	}

	/**
	 * Get the default scopes used by this provider.
	 *
	 * This should not be a complete list of all scopes, but the minimum
	 * required for the provider user interface!
	 *
	 * @return array
	 */
	protected function getDefaultScopes()
	{
		return [];
	}

	/**
	 * Check a provider response for errors.
	 *
	 * @link   https://developer.github.com/v3/#client-errors
	 * @link   https://developer.github.com/v3/oauth/#common-errors-for-the-access-token-request
	 * @throws IdentityProviderException
	 * @param  ResponseInterface $response
	 * @param  string $data Parsed response data
	 * @return void
	 */
	protected function checkResponse(ResponseInterface $response, $data)
	{
		if ($response->getStatusCode() >= 400)
		{
			throw YouthwebIdentityProviderException::clientException($response, $data);
		}
		elseif (isset($data['error']))
		{
			throw YouthwebIdentityProviderException::oauthException($response, $data);
		}
	}

	/**
	 * Generate a user object from a successful user details request.
	 *
	 * @param array $response
	 * @param AccessToken $token
	 * @return League\OAuth2\Client\Provider\ResourceOwnerInterface
	 */
	protected function createResourceOwner(array $response, AccessToken $token)
	{
		$user = new YouthwebResourceOwner($response);

		return $user->setDomain($this->domain);
	}
}
