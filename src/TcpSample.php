<?php
use TCPIPHP\Tcp\PhpTcp;

require 'vendor/autoload.php';

if ($argc < 4) {
    fwrite(STDERR, "Error: php TcpSample.php srcIp dstIp dstPort\n");
    exit(1);
}

$srcIpArg = $argv[1];
$dstIpArg = $argv[2];
$dstPortArg = $argv[3];

$PhpTcp = new PhpTcp($srcIpArg);
$PhpTcp->connect($dstIpArg, $dstPortArg);

