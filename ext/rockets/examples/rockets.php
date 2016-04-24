<?php

/**
 * rockets.php
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to version 3.01 of the PHP license,
 * that is bundled with this package in the file LICENSE, and is
 * available through the world-wide-web at the following url:
 * http://www.php.net/license/3_01.txt
 *
 * If you did not receive a copy of the PHP license and are unable to
 * obtain it through the world-wide-web, please send a note to
 * license@php.net so we can mail you a copy immediately.
 */

/**
 * A php test script show functionality of the extension
 *
 * @copyright  	Copyright (c) 2014 <info@techdivision.com> - TechDivision GmbH
 * @license    	http://www.php.net/license/3_01.txt
 *              PHP License (PHP 3_01)
 * @author      Johann Zelger <zelger@me.com>
 */

// open server socket
echo "rockets_socket ";
var_dump(
    $serverFd = rockets_socket(AF_INET, SOCK_STREAM, SOL_TCP)
);

echo "rockets_bind ";
// bind server socket to local address and port
var_dump(
    rockets_bind($serverFd, '0.0.0.0', 8888, AF_INET)
);

echo "socket get option SO_REUSEADDR";
var_dump(
    rockets_getsockopt($serverFd, SOL_SOCKET, SO_REUSEADDR)
);
echo "socket get option TCP_NODELAY";
var_dump(
    rockets_getsockopt($serverFd, SOL_TCP, TCP_NODELAY)
);

echo "socket set option SO_REUSEADDR";
// socket set options
var_dump(
    rockets_setsockopt($serverFd, SOL_SOCKET, SO_REUSEADDR, true)
);
echo "socket set option TCP_NODELAY";
var_dump(
    rockets_setsockopt($serverFd, SOL_TCP, TCP_NODELAY, true)
);

echo "socket get option SO_REUSEADDR";
var_dump(
    rockets_getsockopt($serverFd, SOL_SOCKET, SO_REUSEADDR)
);
echo "socket get option TCP_NODELAY";
var_dump(
    rockets_getsockopt($serverFd, SOL_TCP, TCP_NODELAY)
);

echo "rockets_listen ";
// listen for connections with backlog of 1024
var_dump(
    rockets_listen($serverFd, 1024)
);

$httpResponseMessage = <<<EOD
HTTP/1.1 200 OK
Server: Rockets/0.1.0
Connection: close
Content-Length: 11
Content-Type: text/html;charset=UTF-8

PHP ROCKETS
EOD;

echo 'listening on port 5556...' . PHP_EOL;

while (1) {
    if ($clientFd = rockets_accept($serverFd)) {
        rockets_recv($clientFd);
        var_dump(rockets_send($clientFd, $httpResponseMessage));
        rockets_close($clientFd);
    }
}