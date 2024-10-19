<?php

//sudo iptables -A OUTPUT -d 3.114.80.201 -p tcp --dport 80 --tcp-flags RST RST -j DROP

$dstArg = $argv[1];
$srcIpArg = $argv[2];

//$src_ip = gethostbyname(gethostname());
//$dst_ip = gethostbyname('vaddy.net');
$src_ip = $srcIpArg;
$dst_ip = gethostbyname($dstArg);

//var_dump($dst_ip); exit;
//var_dump($src_ip); exit;

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
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

//$IP_HDRINCL = 3;
//socket_set_option($socket, IPPROTO_IP,$IP_HDRINCL, 1);

// 送信先情報
//$src_ip = '172.17.0.2';  // 送信元IPアドレス
//$src_ip = '127.0.0.1';  // 送信元IPアドレス
//$src_ip = '192.168.9.128';  // 送信元IPアドレス
//$dst_ip = '172.17.0.1';  // 送信先IPアドレス
//$dst_ip = '3.114.80.201';  // 送信先IPアドレス
//$dst_ip = '127.0.0.1';  // 送信先IPアドレス
//$src_port = 55559;  // 送信元ポート
$src_port  = rand(63000, 65535);
$dst_port = 80;  // 送信先ポート (例: HTTPポート)
//$dst_port = 8888;  // 送信先ポート (例: HTTPポート)

//$src_ip   = '192.168.0.100';  // 送信元IPアドレス
//$dst_ip   = '192.168.0.200';  // 送信先IPアドレス
//$src_port = 12345;  // 送信元ポート
//$dst_port = 80;  // 送信先ポート (例: HTTPポート)
$seq_num  = rand(2057629912, 2157629912);  // シーケンス番号をランダムに設定
//$seq_num  = 877777;  // シーケンス番号をランダムに設定

// IPヘッダーの作成
$ip_ver        = 4;
$ip_header_len = 5;
$tos           = 0;
$total_length  = 20 + 20;  // IPヘッダー(20バイト) + TCPヘッダー(20バイト)
$ip_id         = rand(0, 65535);
$frag_offset   = 0;
$ttl           = 64;
$protocol      = 6;  // TCP
$ip_checksum   = 0;
$src_ip_bin    = inet_pton($src_ip);
$dst_ip_bin    = inet_pton($dst_ip);


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
//$packet = $ip_header . $tcp_header;
$packet = $tcp_header;

// パケットの送信
//socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, 0);
$result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, $dst_port);
var_dump($result);


// パケットの受信
echo "SYNパケット送信。SYN-ACK待機中...\n";

$ackOk = false;
while (true) {
    $buf  = '';
    $from = '';
    $port = 0;

    // 受信バッファサイズを定義
    if (@socket_recvfrom($socket, $buf, 65535, 0, $from, $port) === false) {
        echo "タイムアウト: SYN-ACKパケットを受信できませんでした。\n";
        break;
    }

    //var_dump($buf);

    // 受信データがIPパケットとして正しいか確認
    $ip_header_length = (ord($buf[0]) & 0x0F) * 4;  // IPヘッダーの長さを取得
    $tcp_header_start = $ip_header_length;  // TCPヘッダーの開始位置

    // TCPヘッダーを解析
    $tcp_segment = substr($buf, $tcp_header_start, 20);  // TCPヘッダー部分だけ抜き出す
    $tcp_flags   = ord($tcp_segment[13]);  // TCPヘッダー内の13バイト目がフラグ

    var_dump($tcp_flags);
    var_dump(bin2hex($tcp_segment));

    $synAckSeqNum = unpack('Nint', substr($tcp_segment, 4, 4))['int'];
    $synAckAckNum = unpack('Nint', substr($tcp_segment, 8, 4))['int'];
    //var_dump($seqNum);
    //var_dump($ackNum);

    if (intval($synAckAckNum) === $seq_num + 1) {
        echo "SYN-ACKパケットack num: $synAckAckNum\n";
    }
    // SYN-ACKのフラグは、SYN (0x02) と ACK (0x10) の両方がセットされている必要がある
    if (($tcp_flags & 0x12) == 0x12) {
        echo "SYN-ACKパケットを受信しました！\n";
        $ackOk = true;
        break;
    }
}

if ($ackOk) {
    echo "=== Send Ack Packet...\n";
    //Ackパケット
    $tcp_header = pack('n', $src_port);  // 送信元ポート
    $tcp_header .= pack('n', $dst_port);  // 送信先ポート
    $tcp_header .= pack('N', $seq_num + 1);  // シーケンス番号
    $tcp_header .= pack('N', $synAckSeqNum + 1);  // 確認応答番号 (SynAckのseq numに+1したもの）
    $tcp_header .= pack('C', 5 << 4);  // ヘッダー長
    $tcp_header .= pack('C', 0x10);  // フラグ (Ackフラグ)
    $tcp_header .= pack('n', 65535);  // ウィンドウサイズ
    $tcp_header .= pack('n', 0);  // チェックサム (後で計算)
    $tcp_header .= pack('n', 0);  // 緊急ポインタ

    // 疑似ヘッダー (TCPチェックサム計算用)
    $pseudo_header = $src_ip_bin . $dst_ip_bin . pack('C', 0) . pack('C', $protocol) . pack('n', strlen($tcp_header));
    $tcp_checksum  = checksum($pseudo_header . $tcp_header);

    // チェックサムを設定
    $tcp_header = substr_replace($tcp_header, pack('n', $tcp_checksum), 16, 2);
    $packet = $tcp_header;
    var_dump(bin2hex($packet));

    // パケットの送信
    $result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, $dst_port);
    var_dump($result);
}

//sleep(5);

if (true) {

    echo "=== Send data Packet...\n";
    //RSTパケット
    $tcp_header = pack('n', $src_port);  // 送信元ポート
    $tcp_header .= pack('n', $dst_port);  // 送信先ポート
    $tcp_header .= pack('N', $seq_num + 1);  // シーケンス番号
    $tcp_header .= pack('N', $synAckSeqNum + 1);  // 確認応答番号 (SynAckのseq numに+1したもの）
    $tcp_header .= pack('C', 5 << 4);  // ヘッダー長
    $tcp_header .= pack('C', 0x18);  // フラグ (PSH/ACKフラグ)
    $tcp_header .= pack('n', 65535);  // ウィンドウサイズ
    $tcp_header .= pack('n', 0);  // チェックサム (後で計算)
    $tcp_header .= pack('n', 0);  // 緊急ポインタ

    $data = 'GET / HTTP/1.0' . "\r\n\r\n" ;

    // 疑似ヘッダー (TCPチェックサム計算用)
    $pseudo_header = $src_ip_bin . $dst_ip_bin . pack('C', 0) . pack('C', $protocol) . pack('n', strlen($tcp_header) + strlen($data));
    $tcp_checksum  = checksum($pseudo_header . $tcp_header . $data);

    // チェックサムを設定
    $tcp_header = substr_replace($tcp_header, pack('n', $tcp_checksum), 16, 2);
    $packet = $tcp_header . $data;
    var_dump(bin2hex($packet));

    // パケットの送信
    $result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, $dst_port);
    var_dump($result);

    while (true) {
        $buf  = '';
        $from = '';
        $port = 0;

        echo "=== receiving data \n";

        // 受信バッファサイズを定義
        if (@socket_recvfrom($socket, $buf, 65535, 0, $from, $port) === false) {
            echo "タイムアウト: SYN-ACKパケットを受信できませんでした。\n";
            break;
        }

        var_dump(bin2hex($buf));

        // 受信データがIPパケットとして正しいか確認
        $ip_header_length = (ord($buf[0]) & 0x0F) * 4;  // IPヘッダーの長さを取得
        $tcp_header_start = $ip_header_length;  // TCPヘッダーの開始位置

        // TCPヘッダーを解析
        $tcp_segment = substr($buf, $tcp_header_start, 20);  // TCPヘッダー部分だけ抜き出す
        var_dump(ord($tcp_segment[12]));
        var_dump(ord($tcp_segment[12]) >> 4);
        $tcp_header_size = (ord($tcp_segment[12]) >> 4) * 4;
        $tcp_flags   = ord($tcp_segment[13]);  // TCPヘッダー内の13バイト目がフラグ
        var_dump("header size: ". $tcp_header_size);
        $data = substr($buf, $tcp_header_start + $tcp_header_size);

        var_dump("data: ". $data);

        var_dump($tcp_flags);
        var_dump(bin2hex($tcp_segment));

        $synAckSeqNum = unpack('Nint', substr($tcp_segment, 4, 4))['int'];
        $synAckAckNum = unpack('Nint', substr($tcp_segment, 8, 4))['int'];
        var_dump($synAckSeqNum);
        var_dump($synAckAckNum);


        // SYN-ACKのフラグは、SYN (0x02) と ACK (0x10) の両方がセットされている必要がある
        if ($tcp_flags == 16) {
            echo "ACKパケットを受信しました！\n";
        } else {
            echo "ack以外のパケットを受信しました！\n";
            break;
        }
    }

}

if (false) {

    echo "=== Send FinAck Packet...\n";
    //RSTパケット
    $tcp_header = pack('n', $src_port);  // 送信元ポート
    $tcp_header .= pack('n', $dst_port);  // 送信先ポート
    $tcp_header .= pack('N', $seq_num + 1);  // シーケンス番号
    $tcp_header .= pack('N', 0);  // 確認応答番号 (ACKなし)
    $tcp_header .= pack('C', 5 << 4);  // ヘッダー長
    $tcp_header .= pack('C', 0x11);  // フラグ (Finフラグ)
    $tcp_header .= pack('n', 65535);  // ウィンドウサイズ
    $tcp_header .= pack('n', 0);  // チェックサム (後で計算)
    $tcp_header .= pack('n', 0);  // 緊急ポインタ

    // 疑似ヘッダー (TCPチェックサム計算用)
    $pseudo_header = $src_ip_bin . $dst_ip_bin . pack('C', 0) . pack('C', $protocol) . pack('n', strlen($tcp_header));
    $tcp_checksum  = checksum($pseudo_header . $tcp_header);

    // チェックサムを設定
    $tcp_header = substr_replace($tcp_header, pack('n', $tcp_checksum), 16, 2);
    $packet = $tcp_header;
    var_dump(bin2hex($packet));

    // パケットの送信
    $result = socket_sendto($socket, $packet, strlen($packet), 0, $dst_ip, $dst_port);
    var_dump($result);
}

socket_close($socket);