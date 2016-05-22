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