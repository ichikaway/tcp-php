<?php
namespace TCPIPHP\Tcp;

class PhpTcp
{
    private string $srcIp;

    private string $dstIp;
    private int $port;

    private TcpController $TcpController;

    /**
     * @param string $srcIp
     */
    public function __construct(string $srcIp)
    {
        $this->srcIp = $srcIp;
    }


    public function connect(string $dstIp, int $dstPort)
    {
        //do 3 way handshake
        $socket = socket_create(AF_INET, SOCK_RAW, SOL_TCP);
        if ($socket === false) {
            die("ソケットの作成に失敗しました: " . socket_strerror(socket_last_error()));
        }
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 5, 'usec' => 0]);

        $this->TcpController= new TcpController($socket, $this->srcIp, $dstIp, $dstPort);
        $this->TcpController->handshake();

        socket_close($socket);
    }

    public function read()
    {
        //read data and return ack
    }

    public function write()
    {
        // write data and recv ack
    }

    public function close()
    {
        // fin
    }
}