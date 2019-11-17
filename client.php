<?php
error_reporting(E_ALL);

$address = '127.0.0.1';
$service_port = 12000;

// ソケット作成
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit();
}

// 接続する
$result = socket_connect($socket, $address, $service_port);
if ($result === false) {
    echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
    exit();
} else {
    echo "OK.\n";
}

do {
    $in = readline('> ');
    if (trim($in) == 'quit') {
        break;
    }
    //$in = hex2bin("f0f1f2f3f4f5");
    socket_write($socket, $in, strlen($in));

    $buf = socket_read($socket, 2048);
    if ($buf === false) {
        echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
        exit();
    }
    //$buf = bin2hex($buf);

    if ($buf === '') {
        break;
    }

    echo $buf . '（' . strlen($buf) . "）\n";
} while (true);

echo "Closing socket...";
socket_close($socket);
echo "OK.\n\n";
