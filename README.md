# Youthweb Provider for OAuth 2.0 Client

[![Latest Version](https://img.shields.io/github/release/youthweb/oauth2-youthweb.svg)](https://github.com/youthweb/oauth2-youthweb/releases)
[![Software License GLPv3](http://img.shields.io/badge/License-GPLv3-brightgreen.svg)](LICENSE)
[![Build Status](http://img.shields.io/travis/youthweb/oauth2-youthweb.svg)](https://travis-ci.org/youthweb/oauth2-youthweb)
[![Coverage Status](https://coveralls.io/repos/youthweb/oauth2-youthweb/badge.svg?branch=master&service=github)](https://coveralls.io/github/youthweb/oauth2-youthweb?branch=master)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/youthweb/youthweb-api?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

This package provides [Youthweb OAuth 2.0 support](https://developer.youthweb.net/api_general_oauth2.html) for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

## Installation

To install, use composer:

```
composer require youthweb/oauth2-youthweb
```

## Usage

Usage is the same as The League's OAuth client, using `\Youthweb\OAuth2\Client\Provider\Youthweb` as the provider.

### Authorization Code Flow

```php
$provider = new Youthweb\OAuth2\Client\Provider\Youthweb([
    'clientId'          => '{youthweb-client-id}',
    'clientSecret'      => '{youthweb-client-secret}',
    'redirectUri'       => 'https://example.org/callback-url',
    'apiVersion'        => '0.15', // optional,
]);

if ( ! isset($_GET['code']) )
{
    // If we don't have an authorization code then get one
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['oauth2state'] = $provider->getState();
    header('Location: '.$authUrl);
    exit;
}
// Check given state against previously stored one to mitigate CSRF attack
elseif ( empty($_GET['state']) || ($_GET['state'] !== $_SESSION['oauth2state']) )
{
    unset($_SESSION['oauth2state']);
    exit('Invalid state');
}
else
{
    // Try to get an access token (using the authorization code grant)
    $token = $provider->getAccessToken('authorization_code', [
        'code' => $_GET['code']
    ]);

    // Optional: Now you have a token you can look up a users profile data
    try
    {
        // We got an access token, let's now get the user's details
        $user = $provider->getResourceOwner($token);

        // Use these details to create a new profile
        printf('Hello %s!', $user->getNickname());
    }
    catch (Exception $e)
    {
        // Failed to get user details
        exit('Oh dear...');
    }

    // Use this to interact with an API on the users behalf
    echo $token->getToken();
}
```

### Managing Scopes

When creating your Youthweb authorization URL, you can specify the state and scopes your application may authorize.

```php
$options = [
    'state' => 'OPTIONAL_CUSTOM_CONFIGURED_STATE',
    'scope' => ['user:read', 'user:email'] // array or string
];

$authorizationUrl = $provider->getAuthorizationUrl($options);
```
If neither are defined, the provider will utilize internal defaults.

At the time of authoring this documentation, the following scopes are available with Youthweb-API 0.15:

- post:read
- post:write
- user:read
- user:email

See [here](https://developer.youthweb.net/api_general_scopes.html) for more information.

## Testing

``` bash
$ ./vendor/bin/phpunit
```

## Contributing

Please feel free to fork and sending Pull Requests. This project follows [Semantic Versioning 2](http://semver.org).

## Credits

- [All Contributors](https://github.com/youthweb/oauth2-youthweb/contributors)

## License

GPL3. Please see [License File](LICENSE) for more information.
