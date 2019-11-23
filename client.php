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

$in = readline('Please Input Alphabet[a-d] > ');

do {
    //$in = hex2bin("f0f1f2f3f4f5");

    // ソケットに書き込む
    socket_write($socket, $in, strlen($in));

    if (trim($in) == 'quit') {
        break;
    }

    $buf = socket_read($socket, 2048);
    if ($buf === false) {
        echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($socket)) . "\n";
        exit();
    }
    //$buf = bin2hex($buf);

    if ($buf === '') {
        break;
    }

    // 送信文字列を作る
    $talkback = '';
    switch ($buf) {
        case 'a':
            $talkback = 'b';
            break;
        case 'b':
            $talkback = 'c';
            break;
        case 'c':
            $talkback = 'd';
            break;
        case 'd':
            $talkback = 'e';
            break;
        default:
            break 2;
    }

    // 受信文字列と送信文字列を出力
    echo $buf . ' > '. $talkback . "\n";

    // 次の繰り返しで送信される文字列として格納
    $in = $talkback;
} while (true);

echo "Closing socket...";
socket_close($socket);
echo "OK.\n\n";
