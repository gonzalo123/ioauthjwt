socket.io websockets auth  via JSON Web Token
======

## install dependencies

```
composer install
npm install
bower install
```

## run servers

```
php -S 0.0.0.0:8080 -t www
node io/server.js
```

## example:

socket.io server
```js
var io = require('socket.io')(3000),
    jwt = require('jsonwebtoken'),
    secret = "my_super_secret_key";

io.use(function (socket, next) {
    var token = socket.handshake.query.token,
        decodedToken;
    try {
        decodedToken = jwt.verify(token, secret);
        console.log("token valid for user", decodedToken.user);
        socket.connectedUser = decodedToken.user;
        next();
    } catch (err) {
        console.log(err);
        next(new Error("not valid token"));
        //socket.disconnect();
    }
});

io.on('connection', function (socket) {
    console.log('Connected! User: ', socket.connectedUser);
});
```

Client
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
Welcome {{ user }}!

<script src="http://localhost:3000/socket.io/socket.io.js"></script>
<script src="/assets/jquery/dist/jquery.js"></script>

<script>
    var socket;
    $(function () {
        $.getJSON("/getIoConnectionToken", function (jwt) {
            socket = io('http://localhost:3000', {
                query: 'token=' + jwt
            });

            socket.on('connect', function () {
                console.log("connected!");
            });

            socket.on('error', function (err) {
                console.log(err);
            });
        });
    });
</script>

</body>
</html>
```

Backend
```php
<?php
include __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Silex\Application;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

$app = new Application([
    'secret' => "my_super_secret_key",
    'debug' => true
]);
$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider(), [
    'twig.path' => __DIR__ . '/../views',
]);

$app->get('/', function (Application $app) {
    return $app['twig']->render('home.twig');
});
$app->get('/login', function (Application $app) {
    $username = $app['request']->server->get('PHP_AUTH_USER', false);
    $password = $app['request']->server->get('PHP_AUTH_PW');
    if ('gonzalo' === $username && 'password' === $password) {
        $app['session']->set('user', ['username' => $username]);

        return $app->redirect('/private');
    }
    $response = new Response();
    $response->headers->set('WWW-Authenticate', sprintf('Basic realm="%s"', 'site_login'));
    $response->setStatusCode(401, 'Please sign in.');

    return $response;
});

$app->get('/getIoConnectionToken', function (Application $app) {
    $user = $app['session']->get('user');
    if (null === $user) {
        throw new AccessDeniedHttpException('Access Denied');
    }

    $jwt = JWT::encode([
        // I can use "exp" reserved claim. It's cool. My connection token is only available
        // during a period of time. The problem is if I restart the io server. Client will
        // try to re-connect using this token and it's expired.
        //"exp"  => (new \DateTimeImmutable())->modify('+10 second')->getTimestamp(),
        "user" => $user
    ], $app['secret']);

    return $app->json($jwt);
});

$app->get('/private', function (Application $app) {
    $user = $app['session']->get('user');

    if (null === $user) {
        throw new AccessDeniedHttpException('Access Denied');
    }

    $userName = $user['username'];

    return $app['twig']->render('private.twig', [
        'user'  => $userName
    ]);
});
$app->run();
```