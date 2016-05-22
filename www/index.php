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