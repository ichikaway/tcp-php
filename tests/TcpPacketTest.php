<?php

namespace tests;

use TCPIPHP\Tcp\TcpPacket;
use PHPUnit\Framework\TestCase;
use TCPIPHP\Tcp\TcpUtil;

class TcpPacketTest extends TestCase
{

    public function testCreateSynPacket()
    {
        $TcpPacket = new TcpPacket(srcIp: '192.168.9.128', srcPort: '55555', dstIp: '192.168.9.101', dstPort: 80);
        $synFlag = TcpUtil::createFlagByte(syn: 1);
        $tcpPacket = $TcpPacket->createTcpPacket(seqNum:'55555', ackNum: 0, flag: $synFlag, data: '');

        //var_dump(bin2hex($tcpPacket));

        $expect = 'd90300500000d903000000005002ffff69550000'; //tcpdumpしたhexデータ
        $this->assertEquals($expect, bin2hex($tcpPacket));
    }
}
