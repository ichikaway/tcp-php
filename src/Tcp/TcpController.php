<?php
namespace TCPIPHP\Tcp;

class TcpController
{
    private string $srcIp;
    private int $srcPort;

    private string $dstIp;
    private int $dstPort;

    private $socket;

    private int $seqNum;
    private int $ackNum;

    private TcpPacket $TcpPacket;

    /**
     * @param string $srcIp
     * @param string $dstIp
     * @param int $dstPort
     */
    public function __construct($socket, string $srcIp, string $dstIp, int $dstPort)
    {
        $this->socket = $socket;
        $this->srcIp = $srcIp;
        $this->dstIp = $dstIp;
        $this->dstPort = $dstPort;

        $this->srcPort = rand(63000, 64000);
        $this->seqNum = rand(2100000000, 2200000000);  // シーケンス番号をランダムに設定

        $this->TcpPacket = new TcpPacket($this->srcIp, $this->srcPort, $this->dstIp, $this->dstPort);
    }

    public function close()
    {
        socket_close($this->socket);
    }
    public function handshake()
    {
        // syn packet
        $synFlag = TcpUtil::createFlagByte(syn: 1);
        $packet = $this->TcpPacket->createTcpPacket(seqNum:$this->seqNum, ackNum: 0,flag: $synFlag,data: '');
        $result = socket_sendto($this->socket, $packet, strlen($packet), 0, $this->dstIp, $this->dstPort);
        var_dump($result);
        // パケットの受信
        echo "SYNパケット送信。SYN-ACK待機中...\n";
        $this->receive();

    }

    public function receive()
    {
        while (true) {
            echo "start receive\n";
            $buf  = '';
            $from = '';
            $port = 0;

            // 受信バッファサイズを定義
            if (@socket_recvfrom($this->socket, $buf, 65535, 0, $from, $port) === false) {
                echo "タイムアウト: TCPパケットを受信できませんでした。\n";
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

            $recvSeqNum = unpack('Nint', substr($tcp_segment, 4, 4))['int'];
            $recvAckNum = unpack('Nint', substr($tcp_segment, 8, 4))['int'];

            $this->ackNum = $recvSeqNum;

            // SYN-ACKのフラグは、SYN (0x02) と ACK (0x10) の両方がセットされている必要がある
            if (($tcp_flags & 0x12) == 0x12) {
                echo "SYN-ACKパケットを受信しました！\n";
                $this->seqNum++;
                $this->ackNum++;
                if (intval($recvAckNum) === $this->seqNum) {
                    echo "SYN-ACKパケットack num: $recvAckNum\n";
                }

                $flag = TcpUtil::createFlagByte(ack: 1);
                $packet = $this->TcpPacket->createTcpPacket(seqNum:$this->seqNum, ackNum: $this->ackNum,flag: $flag, data: '');
                $result = socket_sendto($this->socket, $packet, strlen($packet), 0, $this->dstIp, $this->dstPort);
                break;
            }
        }

        return null;
    }
}