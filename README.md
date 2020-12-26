# Slim3 Session Middleware

Simple session middleware for the Slim Framework. Also included is a useful helper class that allows PHP built-in session management.

Forked from https://github.com/andrewdyer/slim3-session-middleware

## License

Licensed under MIT. Totally free for private or commercial projects.

## Installation

```bash
composer require k-ko/slim3-session-middleware
```

## Usage

```php
<?php

$app = new \Slim\App();

$app->add(new \Middleware\SessionMiddleware([
    'autorefresh'   => true,
    'name'          => 'myapp_session',
    'lifetime'      => '1 hour',
]));

$app->get('/', function (Request $request, Response $response) use ($container) {
    if (!isset($container['session']['loggedIn'])) {
        // ...
    }
    // ...
});

$app->run();
```

### Supported Options

| Option | Type | Default | Description |
| --- | --- | --- | --- |
| `autorefresh` | boolean | `false` | If you want session to be refresh when user activity is made (interaction with server). |
| `domain` | tring | `null` | Cookie domain, for example 'www.php.net'. To make cookies visible on  all subdomains then the domain must be prefixed with a dot like '.php.net'. |
| `handler` | mixed | `null` | Custom session handler class or object. Must implement `SessionHandlerInterface` as required by PHP. |
| `httponly` | boolean | `false` | If set to true then PHP will attempt to send the httponly flag when setting the session cookie. |
| `ini_settings` | array | `null` | Associative array of custom session configuration. |
| `lifetime` | int or string | `"20 minutes"` | The lifetime of the session cookie. Can be set to any value which `strtotime` can parse. |
| `name` | string | `"session"` | Name for the session cookie. Defaults to `session` instead of PHP's `PHPSESSID`. |
| `path` |string | `"/"` | The path on the domain where the cookie will work. Use a single slash ('/') for all paths on the domain. |
| `secure` | boolean | `false` | Cookies will only be sent over secure connections if true. |


### Session Helper

The `\Session\Helper` class can be attached to your app container:

```php
$container = $app->getContainer();

$container['session'] = function ($container) {
    return new \Session\Helper();
};
```

The helper class can be used to check if a session variable exists in addition to setting, getting and deleting session variables.

```php
$app->get('/', function (Request $request, Response $response) use ($container) {
    // Check if variable exists
    $exists = $container['session']->has('my_key');
    $exists = isset($container['session']->my_key);
    $exists = isset($container['session']['my_key']);

    // Get variable value
    $value = $container['session']->get('my_key', 'default');
    $value = $container['session']->my_key;
    $value = $container['session']['my_key'];

    // Take variable value out from session
    $value = $container['session']->take('my_key', 'default');

    // Set variable value
    $container['session']->set('my_key', 'my_value');
    $container['session']->my_key = 'my_value';
    $container['session']['my_key'] = 'my_value';

    // Remove variable
    $container['session']->remove('my_key');
    unset($container['session']->my_key);
    unset($container['session']['my_key']);
});
```

## Support

If you believe you have found an issue, please report it using the [issue tracker](https://github.com/k-ko/slim3-session-middleware/issues), or better yet, fork the repository and submit a pull request.

## Useful Links

* [Slim Framework](https://www.slimframework.com)
