# Youthweb Provider for OAuth 2.0 Client

This package provides Youthweb OAuth 2.0 support for the PHP League's [OAuth 2.0 Client](https://github.com/thephpleague/oauth2-client).

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

At the time of authoring this documentation, the following scopes are available:

- user:read
- user:email

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
