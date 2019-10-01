<?php

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
