<?php

// TCPチェックサムを計算するための関数
function checksum($data)
{
    $sum = 0;
    $len = strlen($data);

    for ($i = 0; $i < $len; $i += 2) {
        $word = ord($data[$i]) << 8;
        if ($i + 1 < $len) {
            $word += ord($data[$i + 1]);
        }
        $sum += $word;
    }

    while ($sum >> 16) {
        $sum = ($sum & 0xFFFF) + ($sum >> 16);
    }

    return ~($sum & 0xFFFF);
}

// Rawソケットの作成 (IPv4, Raw Socket, TCP)
$socket = socket_create(AF_INET, SOCK_RAW, SOL_TCP);
if ($socket === false) {
    die("ソケットの作成に失敗しました: " . socket_strerror(socket_last_error()));
}

// ソケットに受信タイムアウトを設定 (2秒)
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 2, 'usec' => 0]);

$IP_HDRINCL = 3;
socket_set_option($socket, IPPROTO_IP,$IP_HDRINCL, 1);

// 送信先情報
//$src_ip = '172.17.0.2';  // 送信元IPアドレス
$src_ip = '192.168.9.128';  // 送信先IPアドレス
//$dst_ip = '3.114.80.201';  // 送信先IPアドレス
$dst_ip = '192.168.9.101';  // 送信先IPアドレス
$src_port = 55555;  // 送信元ポート
//$dst_port = 443;  // 送信先ポート (例: HTTPポート)
$dst_port = 80;  // 送信先ポート (例: HTTPポート)

//$src_ip   = '192.168.0.100';  // 送信元IPアドレス
//$dst_ip   = '192.168.0.200';  // 送信先IPアドレス
//$src_port = 12345;  // 送信元ポート
//$dst_port = 80;  // 送信先ポート (例: HTTPポート)
//$seq_num  = rand(0, 65535);  // シーケンス番号をランダムに設定
$seq_num  = 55555;  // シーケンス番号をランダムに設定

// IPヘッダーの作成
$ip_ver        = 4;
$ip_header_len = 5;
$tos           = 0;
$total_length  = 20 + 20;  // IPヘッダー(20バイト) + TCPヘッダー(20バイト)
//$ip_id         = rand(0, 65535);
$ip_id         = 12345;
$frag_offset   = 0;
$ttl           = 64;
$protocol      = 6;  // TCP
$ip_checksum   = 0;
$src_ip_bin    = inet_pton($src_ip);
$dst_ip_bin    = inet_pton($dst_ip);

// IPヘッダー (20バイト)
$ip_header = pack('C', ($ip_ver << 4) + $ip_header_len);  // バージョン(4ビット) + ヘッダー長(4ビット)
$ip_header .= pack('C', $tos);  // サービスタイプ
$ip_header .= pack('n', $total_length);  // 全長
$ip_header .= pack('n', $ip_id);  // 識別子
$ip_header .= pack('n', $frag_offset);  // フラグメントオフセット
$ip_header .= pack('C', $ttl);  // TTL
$ip_header .= pack('C', $protocol);  // プロトコル (TCP)
$ip_header .= pack('n', $ip_checksum);  // チェックサム (後で計算)
$ip_header .= $src_ip_bin;  // 送信元IPアドレス
$ip_header .= $dst_ip_bin;  // 送信先IPアドレス

// チェックサムを計算し、IPヘッダーに差し替える
$ip_checksum = checksum($ip_header);
$ip_header = substr_replace($ip_header, pack('n', $ip_checksum), 10, 2);

// TCPヘッダーの作成 (SYNパケット)
$tcp_header = pack('n', $src_port);  // 送信元ポート
$tcp_header .= pack('n', $dst_port);  // 送信先ポート
$tcp_header .= pack('N', $seq_num);  // シーケンス番号
$tcp_header .= pack('N', 0);  // 確認応答番号 (ACKなし)
$tcp_header .= pack('C', 5 << 4);  // ヘッダー長
$tcp_header .= pack('C', 0x02);  // フラグ (SYNフラグ)
//$tcp_header .= pack('C', 0x12);  // フラグ (SYNフラグ)
$tcp_header .= pack('n', 65535);  // ウィンドウサイズ
$tcp_header .= pack('n', 0);  // チェックサム (後で計算)
$tcp_header .= pack('n', 0);  // 緊急ポインタ

// 疑似ヘッダー (TCPチェックサム計算用)
$pseudo_header = $src_ip_bin . $dst_ip_bin . pack('C', 0) . pack('C', $protocol) . pack('n', strlen($tcp_header));
$tcp_checksum  = checksum($pseudo_header . $tcp_header);

// チェックサムを設定
$tcp_header = substr_replace($tcp_header, pack('n', $tcp_checksum), 16, 2);

// 最終的なパケット (IPヘッダー + TCPヘッダー)
$packet = $ip_header . $tcp_header;
//$packet = $tcp_header;

// パケットの送信
//socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, 0);
$result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, $dst_port);
var_dump($result);


// パケットの受信
echo "SYNパケット送信。SYN-ACK待機中...\n";

    $buf  = '';
    //$from = '';
    //$port = 0;
    $from = $dst_ip;
    $port = $dst_port;

    // 受信バッファサイズを定義
    if (@socket_recvfrom($socket, $buf, 65535, 0, $from, $port) === false) {
        echo "タイムアウト: SYN-ACKパケットを受信できませんでした。\n";
    }
    // 受信バッファサイズを定義
    if (@socket_recvfrom($socket, $buf, 65535, 0, $from, $port) === false) {
        echo "タイムアウト: SYN-ACKパケットを受信できませんでした。\n";
        exit;
    }



while (true) {
    $buf  = '';
    $from = '';
    $port = 0;

    // 受信バッファサイズを定義
    if (@socket_recvfrom($socket, $buf, 65535, 0, $from, $port) === false) {
        echo "タイムアウト: SYN-ACKパケットを受信できませんでした。\n";
        break;
    }

    // 受信データがIPパケットとして正しいか確認
    $ip_header_length = (ord($buf[0]) & 0x0F) * 4;  // IPヘッダーの長さを取得
    $tcp_header_start = $ip_header_length;  // TCPヘッダーの開始位置

    // TCPヘッダーを解析
    $tcp_segment = substr($buf, $tcp_header_start, 20);  // TCPヘッダー部分だけ抜き出す
    $tcp_flags   = ord($tcp_segment[13]);  // TCPヘッダー内の13バイト目がフラグ

    // SYN-ACKのフラグは、SYN (0x02) と ACK (0x10) の両方がセットされている必要がある
    if (($tcp_flags & 0x12) == 0x12) {
        echo "SYN-ACKパケットを受信しました！\n";
        var_dump(bin2hex($buf));
        break;
    }
}

socket_close($socket);


/*
// TCPチェックサムを計算するための関数
function checksum($data) {
    $sum = 0;
    $len = strlen($data);

    for ($i = 0; $i < $len; $i += 2) {
        $word = ord($data[$i]) << 8;
        if ($i + 1 < $len) {
            $word += ord($data[$i + 1]);
        }
        $sum += $word;
    }

    while ($sum >> 16) {
        $sum = ($sum & 0xFFFF) + ($sum >> 16);
    }

    return ~($sum & 0xFFFF);
}

// Rawソケットを作成 (IPv4, Raw Socket, TCP)
$socket = socket_create(AF_INET, SOCK_RAW, SOL_TCP);
if ($socket === false) {
    die("ソケットの作成に失敗しました: " . socket_strerror(socket_last_error()));
}

// 送信先情報
$src_ip = '172.17.0.2';  // 送信元IPアドレス
#$dst_ip = '172.17.0.1';  // 送信先IPアドレス
$dst_ip = '3.114.80.201';  // 送信先IPアドレス
$src_port = 52345;  // 送信元ポート
$dst_port = 9876;  // 送信先ポート (例: HTTPポート)
$seq_num = rand(0, 65535);  // シーケンス番号をランダムに設定

// IPヘッダーの作成
$ip_ver = 4;
$ip_header_len = 5;
$tos = 0;
$total_length = 20 + 20;  // IPヘッダー(20バイト) + TCPヘッダー(20バイト)
$ip_id = rand(0, 65535);
$frag_offset = 0;
$ttl = 64;
$protocol = 6;  // TCP
$ip_checksum = 0;
$src_ip_bin = inet_pton($src_ip);
$dst_ip_bin = inet_pton($dst_ip);

// IPヘッダー (20バイト)
$ip_header = pack('C', ($ip_ver << 4) + $ip_header_len);  // バージョン(4ビット) + ヘッダー長(4ビット)
$ip_header .= pack('C', $tos);  // サービスタイプ
$ip_header .= pack('n', $total_length);  // 全長
$ip_header .= pack('n', $ip_id);  // 識別子
$ip_header .= pack('n', $frag_offset);  // フラグメントオフセット
$ip_header .= pack('C', $ttl);  // TTL
$ip_header .= pack('C', $protocol);  // プロトコル (TCP)
$ip_header .= pack('n', $ip_checksum);  // チェックサム (後で計算)
$ip_header .= $src_ip_bin;  // 送信元IPアドレス
$ip_header .= $dst_ip_bin;  // 送信先IPアドレス

// TCPヘッダーの作成 (SYNパケット)
$tcp_header = pack('n', $src_port);  // 送信元ポート
$tcp_header .= pack('n', $dst_port);  // 送信先ポート
$tcp_header .= pack('N', $seq_num);  // シーケンス番号
$tcp_header .= pack('N', 0);  // 確認応答番号 (ACKなし)
$tcp_header .= pack('C', 5 << 4);  // ヘッダー長
$tcp_header .= pack('C', 0x02);  // フラグ (SYNフラグ)
$tcp_header .= pack('n', 65535);  // ウィンドウサイズ
$tcp_header .= pack('n', 0);  // チェックサム (後で計算)
$tcp_header .= pack('n', 0);  // 緊急ポインタ

// 疑似ヘッダー (TCPチェックサム計算用)
$pseudo_header = $src_ip_bin . $dst_ip_bin . pack('C', 0) . pack('C', $protocol) . pack('n', strlen($tcp_header));
$tcp_checksum = checksum($pseudo_header . $tcp_header);

// チェックサムを設定
$tcp_header = substr_replace($tcp_header, pack('n', $tcp_checksum), 16, 2);

// 最終的なパケット (IPヘッダー + TCPヘッダー)
$packet = $ip_header . $tcp_header;
var_dump(strlen($packet));

// パケットの送信
//$result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, $dst_port);
$result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, 0);
var_dump($result);
socket_close($socket);
*/