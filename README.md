# TCP implementation with PHP

PHPのscoketを使ってTCPプロトコルを実装  

## 注意点

- raw socketを使うためLinuxのroot権限が必要
- OSのTCP管理とバッティングするため、RSTパケットは常にDropさせる必要あり（3way handshakeが成立しないため）
    - 例:  sudo iptables -A OUTPUT  -d 192.168.9.101 --dport 80 -p tcp --tcp-flags RST RST -j DROP

 
