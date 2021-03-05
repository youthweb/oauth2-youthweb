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

namespace Youthweb\OAuth2\Client\Provider;

use Youthweb\OAuth2\Client\Provider\Exception\YouthwebIdentityProviderException;
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
     * Api version
     *
     * @var string
     */
    public $apiVersion = '0.18';

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->domain . '/auth/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->domain . '/auth/access_token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->apiDomain . '/me';
    }

    /**
     * Returns an authenticated PSR-7 request instance for Youthweb-API.
     *
     * @param string             $method
     * @param string             $url
     * @param AccessToken|string $token
     * @param array              $options any of "headers", "body", and "protocolVersion"
     *
     * @return RequestInterface
     */
    public function getAuthenticatedRequest($method, $url, $token, array $options = [])
    {
        $options['headers']['Accept'] = 'application/vnd.api+json, application/vnd.api+json; net.youthweb.api.version=' . $this->apiVersion;
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
     * @see   http://jsonapi.org/format/1.0/#errors
     * @see   https://tools.ietf.org/html/rfc6749#section-5.2
     *
     * @param ResponseInterface $response
     * @param string            $data     Parsed response data
     *
     * @throws IdentityProviderException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            // check for JSON API errors
            if (isset($data['errors'])) {
                throw YouthwebIdentityProviderException::clientException($response, $data);
            }

            // It must be an oauth2 error
            throw YouthwebIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array       $response
     * @param AccessToken $token
     *
     * @return League\OAuth2\Client\Provider\ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        $user = new YouthwebResourceOwner($response);

        return $user->setDomain($this->domain);
    }
}
