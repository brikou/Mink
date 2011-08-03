<?php

namespace Tests\Behat\Mink;

use Behat\Mink\Driver\Zombie\Server;

class ServerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->server = new Server(__DIR__.'/server-fixtures/test_server.js');
    }

    public function tearDown()
    {
        unset($this->server);
    }

    public function testJsPathForDefaultServer()
    {
        $server = new Server();
        $this->assertContains('src/Behat/Mink/Driver/Zombie/server.js', $server->getJsPath());
    }

    public function testDefaultThreshold()
    {
        $server = new Server();
        $this->assertEquals(200000, $server->getThreshold());
    }

    public function testInvalidValueForThreshold()
    {
        $server = new Server('/path/to/server.js', 'INVALID');
        $this->assertEquals(200000, $server->getThreshold());

        $server->setThreshold('ALSO_INVALID!!!');
        $this->assertEquals(200000, $server->getThreshold());
    }

    public function testStartServer()
    {
        $this->server->start();
        $this->assertEquals('Behat\Mink\Driver\Zombie\Connection', get_class($this->server->getConnection()));
        $this->assertTrue($this->server->isRunning());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testStartServerWithBadJsPath()
    {
        $server = new Server('non_exististing_server.js');
        $server->start();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testStartAlreadyRunningServer()
    {
        $this->server->start();
        $this->server->start();
    }

    public function testStopServer()
    {
        $this->server->start();

        $this->server->stop();
        $this->assertNull($this->server->getConnection());
        $this->assertFalse($this->server->isRunning());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testStopNotRunningServer()
    {
        $this->server->stop();
    }

    public function testRestartServer()
    {
        $this->server->restart();
        $this->assertEquals('Behat\Mink\Driver\Zombie\Connection', get_class($this->server->getConnection()));
        $this->assertTrue($this->server->isRunning());
    }

    public function testExecJS()
    {
        $this->server->start();

        $connMock = $this->getMockBuilder('Behat\Mink\Driver\Zombie\Connection')
                         ->disableOriginalConstructor()
                         ->getMock();
        $connMock->expects($this->once())
                 ->method("socketSend")
                 ->with($this->equalTo("alert('Hello World');"));

        $this->server->setConnection($connMock);
        $this->server->execJS("alert('Hello World');");
    }

    public function testEvalJSON()
    {
        $this->server->start();

        $connMock = $this->getMockBuilder('Behat\Mink\Driver\Zombie\Connection')
                         ->disableOriginalConstructor()
                         ->getMock();
        $connMock->expects($this->once())
                 ->method("socketSend")
                 ->with($this->equalTo("stream.end(JSON.stringify(browser.location.toString()));"))
                 ->will($this->returnValue(json_encode('http://example.org/foo.html')));

        $this->server->setConnection($connMock);
        $ret = $this->server->evalJSON("browser.location.toString()");
        $this->assertEquals('http://example.org/foo.html', $ret);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testExecJSForNonRunningServer()
    {
        $this->server->execJS("alert('Hello World');");
    }
}
