<?php

require dirname(__FILE__) . "/../src/FluentLogger/TCPSocket.php";

class TCPSocketTest extends PHPUnit_Framework_TestCase
{
    public function test___construct()
    {
        $socket = new TCPSocket("0.0.0.0", 2000);
        $this->assertEquals("0.0.0.0", $socket->host);
        $this->assertEquals(2000, $socket->port);
        $this->assertEquals(sprintf('tcp://%s:%s', $socket->host, $socket->port), $socket->transport);
    }

    // describe #connect
    public function test_connect_success_when_not_connected()
    {
        // subject(:socket)
        $socket = $this->socket_for_connect();

        // context 'when #is_resource return false'
        $socket->expects($this->any())->method('is_resource')->will($this->onConsecutiveCalls(false));
        //   it 'should call #create once.'
        $socket->expects($this->once())->method('create');
        //   it 'should call set_timeout with 3.'
        $socket->expects($this->once())->method('set_timeout')->with($this->equalTo(3));
        //   it 'should return true.'
        $this->assertEquals(true, $socket->connect());
    }

    public function test_connect_when_connected()
    {
        // subject(:socket)
        $socket = $this->socket_for_connect();

        // context 'when #is_resouce return true'
        $socket->expects($this->any())->method('is_resource') ->will($this->onConsecutiveCalls(true)); 
        //   it 'should never call create.'
        $socket->expects($this->never())->method('create');
        //   it 'should never call set_timeout.'
        $socket->expects($this->never())->method('set_timeout');
        //   it 'should return true.'
        $this->assertEquals(true, $socket->connect());
    }

    // fail test
    public function test_connect_fail_when_not_connected()
    {
        // subject(:socket)
        $socket = $this->socket_for_connect();

        // context 'when #is_resource return false'
        $socket->expects($this->any()) ->method('is_resource')->will($this->onConsecutiveCalls(false));
        //   context 'when #create raise Error'
        $socket->expects($this->once())->method('create')->will($this->throwException(new Exception("fail to create socket.")));
        //     it 'should call #error at least once.'
        $socket->expects($this->atLeastOnce())->method('error');
        //     it 'should return false.'
        $this->assertEquals(false, $socket->connect());
    }

    // describe #write
    public function test_write_success()
    {
        // subject(:socket)
        $socket = $this->socket_for_write();

        // context 'when #fwrite return 2(first call), 3(second call), 5(third call)'
        $socket->expects($this->any())->method('fwrite')->will($this->onConsecutiveCalls(2, 3, 5));
        //   context 'when given 10character to #write'
        //     it 'return true'
        $this->assertEquals(true, $socket->write("aaaaaaaaaa"));
    }

    public function test_write_success_with_just_retry_max()
    {
        // subject(:socket)
        $socket = $this->socket_for_write();

        // context 'when have not finish #write'
        //   context 'when #fwrite three times return 0 in a row'
        //     context 'and after that return Number(except 0) for to finish #write'
        $socket->expects($this->any())->method('fwrite')->will($this->onConsecutiveCalls(2, 3, 0, 0, 0, 5));
        //       it 'return true.'
        $this->assertEquals(false, $socket->write("aaaaaaaaaa"));
    }

    public function test_write_fail_with_over_retry_max()
    {
        // subject(:socket)
        $socket = $this->socket_for_write();

        // context 'when have not finish #write'
        //   context 'when #fwrite three times return 0 in a row'
        //     context 'and after that return 0'
        $socket->expects($this->any())->method('fwrite')->will($this->onConsecutiveCalls(2, 3, 0, 0, 0, 0));
        //       it 'should call #error at least once.'
        $socket->expects($this->atLeastOnce())->method('error');
        //       it 'return false.'
        $this->assertEquals(false, $socket->write("aaaaaaaaaa"));
    }

    public function test_write_fail_with_connection_aborted()
    {
        // subject(:socket)
        $socket = $this->socket_for_write();

        // context 'when have not finish #write'
        //   context 'when #fwrite return empty string'
        $socket->expects($this->any()) ->method('fwrite') ->will($this->onConsecutiveCalls(2, 3, ""));
        //     it 'should call #error at least once.'
        $socket->expects($this->atLeastOnce())->method('error');
        //     it 'should return false.'
        $this->assertEquals(false, $socket->write("aaaaaaaaaa"));
    }

    public function test_write_fail()
    {
        // subject(:socket)
        $socket = $this->socket_for_write();

        // context 'when have not finish #write'
        //   context 'when #fwrite return false'
        $socket->expects($this->any())->method('fwrite')->will($this->onConsecutiveCalls(2, 3, false));
        //     it 'should call #error at least once.'
        $socket->expects($this->atLeastOnce())->method('error');
        //     it 'should return false.'
        $this->assertEquals(false, $socket->write("aaaaaaaaaa"));
    }

    // helper methods
    private function socket_for_connect() {
        return $this->getMock('TCPSocket', array('is_resource', 'create', 'set_timeout', 'error'), array("0.0.0.0", 2000));
    }

    private function socket_for_write() {
        return $this->getMock('TCPSocket', array('fwrite', 'connect', 'error'), array("0.0.0.0", 2000));
    }
}

