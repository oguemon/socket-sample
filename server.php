<?php
error_reporting(E_ALL);

/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting
 * as it comes in. */
ob_implicit_flush();

$address = '127.0.0.1';
$port = 12000;

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

//clients array
$clients = array();

do {
    $read = array($sock);

    $read = array_merge($read,$clients);
    $write = NULL;
    $except = NULL;

    // 変化したソケットの数を読み込み、1以上ある時に続行
    $change_num = socket_select($read, $write, $except, 0);
    if($change_num < 1) {
        continue;
    }

    if (in_array($sock, $read)) {
        // 接続要求が届くまでここで止まる
        // 新しいソケットが届く
        $msgsock = socket_accept($sock);
        if ($msgsock === false) {
            echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
            exit();
        }
        // クライアントソケット配列に追加
        $clients[] = $msgsock;
        // 追加した要素番号を格納
        $key = array_keys($clients, $msgsock);
    }

    // クライアントで回す
    foreach ($clients as $key => $client) { // for each client
        // クライアントが読み込み可能ソケットの中に含まれる
        if (in_array($client, $read)) {
            //socket_set_option($msgsock, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0, "usec"=>100));
            socket_set_nonblock($client);

            // データを読み込む
            $buf = socket_read($client, 2048);
            if ($buf === false) {
                echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($client)) . "\n";
                break 2;
            }

            // 空文字なら次のループ開始まで飛ぶ
            if (!$buf = trim($buf)) {
                continue;
            }

            if ($buf == 'quit') {
                unset($clients[$key]);
                socket_close($client);
                break;
            }

            if ($buf == 'shutdown') {
                socket_close($client);
                break 2;
            }

            // 受信文字列を出力
            echo "$buf\n";

            // 送信文字列を作って送信
            $talkback = "Client {$key} '$buf'.\n";
            socket_write($client, $talkback, strlen($talkback));
        }
    }
} while (true);

socket_close($sock);
