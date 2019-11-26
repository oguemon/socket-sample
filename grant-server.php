<?php
error_reporting(E_ALL);

/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting
 * as it comes in. */
ob_implicit_flush();

$address = '127.0.0.1';
$port = 12001;

// ソケット作成
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($sock === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit();
}

// IPアドレスとポート番号を設定
if (socket_bind($sock, $address, $port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
    exit();
}

// 接続待ちをする(同時に待ち受けられる接続キューの最大個数)
if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
    exit();
}

// 新規要求を認めて新しいソケットを取得
$msgsock = socket_accept($sock);
if ($msgsock === false) {
    echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
    exit();
}

while (true) {
    // データを読み込む
    $read_str = socket_read($msgsock, 2048);
    if ($read_str === false) {
        echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
        break;
    } else if ($read_str === '') {
        // 空文字なら次のループ開始まで飛ぶ
        continue;
    }

    // 受信文字列を出力
    echo $read_str . "\n";

    // 送信文字列を作る
    $write_str = 'Received Hello';

    // 送信文字列を送信
    socket_write($msgsock, $write_str, strlen($write_str));
}

echo "Closing socket...";
socket_close($sock);
echo "OK.\n\n";
