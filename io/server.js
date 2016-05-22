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