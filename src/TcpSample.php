<?php
use TCPIPHP\Tcp\PhpTcp;

require 'vendor/autoload.php';

if ($argc < 2) {
    fwrite(STDERR, "Error: No argument provided.\n");
    exit(1);
}

$dstArg = $argv[1];
$srcIpArg = $argv[2];

$PhpTcp = new PhpTcp($srcIpArg);
$PhpTcp->connect($dstArg, 81);

