<?php
error_reporting(E_ALL);

/* Allow the script to hang around waiting for connections. */
set_time_limit(0);

/* Turn on implicit output flushing so we see what we're getting
 * as it comes in. */
ob_implicit_flush();

$my_address = '127.0.0.1';
$my_port = 12000;

$grand_server_address = '127.0.0.1';
$grand_server_port = 12001;

/**
 * 対クライアント通信の準備
 */
// ソケット作成
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($sock === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit();
}

// IPアドレスとポート番号を設定
if (socket_bind($sock, $my_address, $my_port) === false) {
    echo "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
    exit();
}

// 接続待ちをする(同時に待ち受けられる接続キューの最大個数)
if (socket_listen($sock, 5) === false) {
    echo "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)) . "\n";
    exit();
}

/**
 * 対グランドサーバー通信の準備
 */
// ソケット作成
$sock_grand_server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($sock_grand_server === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
    exit();
}

// 接続する
$result = socket_connect($sock_grand_server, $grand_server_address, $grand_server_port);
if ($result === false) {
    echo "socket_connect() failed: reason: " . socket_strerror(socket_last_error($sock_grand_server)) . "\n";
    exit();
}

// 接続中のクライアントの一覧を格納する配列
$clients = array();

do {
    // 接続監視中の$sockと、グランドサーバーに接続中の$sock_grand_serverで配列を作る
    $read = array($sock, $sock_grand_server);

    // 上の配列と接続中のクライアントリストをマージする
    $read = array_merge($read,$clients);
    $write = NULL;
    $except = NULL;

    // 変化したソケットの数を読み込み、1以上ある時に続行
    $change_num = socket_select($read, $write, $except, 0);
    if($change_num < 1) {
        continue;
    }

    // 新規要求を見張る$sockが変化したソケット一覧$readに含まれていたら
    if (in_array($sock, $read)) {
        // 新規要求を認めて新しいソケットを取得
        $msgsock = socket_accept($sock);
        if ($msgsock === false) {
            echo "socket_accept() failed: reason: " . socket_strerror(socket_last_error($msgsock)) . "\n";
            exit();
        }
        // 新しいソケットをクライアントソケット配列に追加
        $clients[] = $msgsock;
        // 追加した要素番号を格納
        $key = array_keys($clients, $msgsock);
    }

    // グランドサーバーに接続中の$sock_grand_serverが変化したソケット一覧$readに含まれていたら
    if (in_array($sock_grand_server, $read)) {
        // データを読み込む（グランドサーバー側の送信バイト数に合わせる）
        $buf = socket_read($sock_grand_server, 14);
        if ($buf === false) {
            echo "socket_read() failed: reason: " . socket_strerror(socket_last_error($sock_grand_server)) . "\n";
        } else {
            echo 'from GRAND SERVER > ' . $buf . "\n";
        }
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

            // 送信文字列（クライアント向け）を作る
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
                    $talkback = 'd';
                    break;
                default:
                   $talkback = 'e';
            }

            // 受信文字列と送信文字列を出力
            echo $buf . ' > '. $talkback . "\n";

            // 送信文字列を送信
            socket_write($client, $talkback, strlen($talkback));

            // 送信文字列（グランドサーバー向け）を送る
            $talkback_to_grand_server = 'Hello from ' . $key;

            // 送信文字列を送信
            socket_write($sock_grand_server, $talkback_to_grand_server, strlen($talkback_to_grand_server));
        }
    }
} while (true);

socket_close($sock);
socket_close($sock_grand_server);
