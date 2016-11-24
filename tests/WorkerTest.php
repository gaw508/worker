<?php

namespace Gaw508\Worker;

use PHPUnit_Framework_TestCase;

class WorkerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Worker
     */
    private $worker;

    public function setUp()
    {
        $this->worker = $this
            ->getMockBuilder('Gaw508\Worker\Worker')
            ->setMethods(array('work'))
            ->setConstructorArgs(array(__DIR__ . '/data/pid.txt'))
            ->getMockForAbstractClass();
    }

    public function tearDown()
    {
        unlink(__DIR__ . '/data/pid.txt');
    }

    public function testWorkerStarts()
    {
        $this->worker
            ->expects($this->once())
            ->method('work');

        $this->worker->start();
    }

    public function testWorkerDoesntStartsWithRunningProcess()
    {
        $this->worker
            ->expects($this->exactly(1))
            ->method('work');

        // Start one to have a PID created
        $this->assertTrue($this->worker->start());

        // Start again, should not work (as last process is still running)
        $this->assertFalse($this->worker->start());
    }

    public function testWorkerStartsWithFileWithNonRunningProcess()
    {
        file_put_contents(__DIR__ . '/data/pid.txt', 'NotAProcess');

        $this->worker
            ->expects($this->exactly(1))
            ->method('work');

        // Start one to have a PID created
        $this->assertTrue($this->worker->start());

        // Start again, should not work (as last process is still running)
        $this->assertFalse($this->worker->start());
    }
}
