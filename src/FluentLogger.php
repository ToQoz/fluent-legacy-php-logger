<?php

require dirname(__FILE__). "/FluentLogger/TCPSocket.php";
require dirname(__FILE__). "/FluentLogger/FluentMsgFormatter.php";

class FluentLogger
{
    // cache of instance
    static private $instances = array();

    // Contructor
    public function __construct($host, $port)
    {
        $this->tcp_socket = new TCPSocket($host, $port);
    }

    public function __destruct()
    {
        $this->close();
    }

    static public function open($host, $port)
    {
        $key = md5(sprintf('tcp://%s:%s', $host, $port));
        if (!isset(self::$instances[$key])) {
            self::$instances[$key] = new self($host, $port);
        }
        return self::$instances[$key];
    }

    // post to fluentd
    public function post($tag, $log_data)
    {
        $msg = FluentMsgFormatter::for_forward($tag, $log_data);

        $this->tcp_socket->write($msg);
    }

    public function close()
    {
        if ($this->tcp_socket->is_resource()) {
            $this->tcp_socket->close();
        }
    }
}

