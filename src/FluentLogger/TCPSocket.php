<?php

class TCPSocket
{
    // state
    private $is_connected = false;
    // server info
    private $host;
    private $port;
    private $transport;
    // socket resource object
    private $socket;

    // Contructor
    public function __construct($host, $port)
    {
        $this->host = $host ? $host : '0.0.0.0';
        $this->port = $port ? $port : 80;
        $this->transport = sprintf('tcp://%s:%s', $this->host, $this->port);
    }

    // Getter
    public function __get($property)
    {
        if (!$this->is_readable($property)) { throw new Exception($property . " is not readable."); }

        return $this->$property;
    }

    // Setter
    public function __set($property, $value)
    {
        if (!$this->is_writable($property)) { throw new Exception($property . " is not writable."); }

        $this->$property = $value;
        return $this;
    }

    // Helper for validation in setter/getter
    private function is_readable($property)
    {
        return in_array($property, array( 'socket', 'host', 'port', 'transport', 'is_connected' ));
    }

    private function is_writable($property)
    {
        return in_array($property, array());
    }

    public function create()
    {
        $connect_options = STREAM_CLIENT_CONNECT;
        $socket = @stream_socket_client($this->transport, $errno, $errstr, 3, $connect_options);
        if (!$socket) {
            $errors = error_get_last();
            throw new Exception($errors['message']);
        }
        return $socket;
    }

    // connect socket
    public function connect()
    {
        if (!$this->is_resource()) {
            try {
                $this->socket = $this->create();
            } catch (Exception $e) {
                $this->error($e->getMessage());
                return false;
            }
            $this->set_timeout(3);
        }
        return true;
    }

    public function reconnect()
    {
        $this->close();
        return $this->connect();
    }

    // fork from https://github.com/fluent/fluent-logger-php/blob/master/src/Fluent/Logger/FluentLogger.php#L327
    // write socket
    public function write($msg)
    {
        $written = 0;
        $buffer = $msg;
        $length = strlen($buffer);
        $retry = 0;
        $retry_max = 3;

        $this->connect();

        try {
            while ($written < $length) {
                $nwrite = $this->fwrite($buffer);

                // fail
                if ($nwrite === "") {
                    throw new Exception("connection aborted");
                } elseif ($nwrite === false) {
                    throw new Exception("could not write message");
                } elseif ($nwrite === 0) {
                    if ($retry > $retry_max) {
                        throw new Exception("failed fwrite retry: max retry count");
                    }
                    $errors = error_get_last();

                    if ($errors && isset($errors['message']) &&
                        strpos($errors['message'], 'errno=32') !== false
                    ) {
                        $this->reconnect();
                    } else {
                        $this->error("unhandled error detected.");
                    }
                } 

                $written += $nwrite;
                $buffer = substr($msg, $written);
                $retry++;
            }
        } catch (Exception $e) {
            $this->error($e->getMessage());
            return false;
        }

        return true;
    }

    public function fwrite($data) {
        return fwrite($this->socket, $data, strlen($data));
    }

    // close socket
    public function close()
    {
        if ($this->is_resource()) {
            fclose($this->socket);

            $this->is_connected = false;
            $this->socket = null;
        }
    }

    public function set_timeout($time)
    {
        // set read / write timeout.
        stream_set_timeout($this->socket, $time);
    }

    public function is_resource()
    {
        return is_resource($this->socket);
    }

    public function error($e_msg)
    {
        error_log($e_msg);
    }
}

