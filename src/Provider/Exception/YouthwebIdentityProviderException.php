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

namespace Youthweb\OAuth2\Client\Provider\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Psr\Http\Message\ResponseInterface;

class YouthwebIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     *
     * @param ResponseInterface $response
     * @param string            $data     Parsed response data
     *
     * @return YouthwebIdentityProviderException
     */
    public static function clientException(ResponseInterface $response, $data)
    {
        $message = $response->getReasonPhrase();

        // Use only the first error
        if (isset($data['errors'][0]['detail'])) {
            $message = (string) $data['errors'][0]['detail'];
        } elseif (isset($data['errors'][0]['title'])) {
            $message = (string) $data['errors'][0]['title'];
        }

        return static::fromResponse($response, $message);
    }

    /**
     * Creates oauth exception from response.
     *
     * @param ResponseInterface $response
     * @param string            $data     Parsed response data
     *
     * @return YouthwebIdentityProviderException
     */
    public static function oauthException(ResponseInterface $response, $data)
    {
        return static::fromResponse(
            $response,
            isset($data['error']) ? $data['error'] : $response->getReasonPhrase()
        );
    }

    /**
     * Creates identity exception from response.
     *
     * @param ResponseInterface $response
     * @param string            $message
     *
     * @return YouthwebIdentityProviderException
     */
    protected static function fromResponse(ResponseInterface $response, $message = null)
    {
        return new static($message, $response->getStatusCode(), (string) $response->getBody());
    }
}
