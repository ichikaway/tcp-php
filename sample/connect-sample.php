<?php

$tcp_header_size   = ord(hex2bin("50")) >> 4;
var_dump($tcp_header_size);exit;

// サーバーのIPアドレスとポート番号を設定
$server = '192.168.9.101'; // 送信先サーバーのIPアドレス
$port = 80;          // 送信先サーバーのポート番号

// ソケットを作成
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
    die("ソケットの作成に失敗しました: [$errorcode] $errormsg \n");
}

// サーバーに接続
$result = socket_connect($socket, $server, $port);
if ($result === false) {
    $errorcode = socket_last_error($socket);
    $errormsg = socket_strerror($errorcode);
    die("サーバーへの接続に失敗しました: [$errorcode] $errormsg \n");
}

echo "サーバーに接続しました。\n";
//sleep(100);

// 送信するメッセージ
$message = "GET / HTTP/1.1\r\n";

// メッセージを送信
$sent = socket_write($socket, $message, strlen($message));
if ($sent === false) {
    $errorcode = socket_last_error($socket);
    $errormsg = socket_strerror($errorcode);
    die("メッセージの送信に失敗しました: [$errorcode] $errormsg \n");
}

echo "メッセージを送信しました: $message\n";

// 必要に応じてサーバーからのレスポンスを受信
$response = socket_read($socket, 2048, PHP_NORMAL_READ);
if ($response === false) {
    $errorcode = socket_last_error($socket);
    $errormsg = socket_strerror($errorcode);
    die("レスポンスの受信に失敗しました: [$errorcode] $errormsg \n");
} elseif ($response) {
    echo "サーバーからのレスポンス: $response\n";
} else {
    echo "サーバーからのレスポンスはありませんでした。\n";
}

// ソケットを閉じる
socket_close($socket);
echo "ソケットを閉じました。\n";