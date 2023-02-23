<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Manzadey\SentryCronMonitor\CronMonitor;
use Manzadey\SentryCronMonitor\CronMonitorStatus;
use Manzadey\SentryCronMonitor\Exceptions\CronMonitorException;
use PHPUnit\Framework\TestCase;

class CronMonitorTest extends TestCase
{
    protected CronMonitor $monitor;

    protected function setUp() : void
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

    public function testEnvironmentVariables() : void
    {
        $message = 'Copy the .env.example file with the name .env and fill in the variables with up-to-date data for the tests';

        $this->assertNotEmpty($_ENV['PHPUNIT_DSN'], $message);
        $this->assertNotEmpty($_ENV['PHPUNIT_MONITOR_ID'], $message);
    }

    /**
     * @depends testEnvironmentVariables
     */
    public function testCronMonitorProgressAndError() : void
    {
        $monitor = new CronMonitor($_ENV['PHPUNIT_DSN']);

        $monitor->progress($_ENV['PHPUNIT_MONITOR_ID']);
        $this->assertArrayHasKey(CronMonitorStatus::InProgress->value, $monitor->getData());
        $this->assertArrayHasKey('id', $monitor->getDataProgress());

        $monitor->error();
        $this->assertArrayHasKey(CronMonitorStatus::Error->value, $monitor->getData());
        $this->assertArrayHasKey('id', $monitor->getDataError());

        $this->assertCount(0, $monitor->getErrors());
    }

    /**
     * @depends testEnvironmentVariables
     */
    public function testCronMonitorProgressAndOk() : void
    {
        $monitor = new CronMonitor($_ENV['PHPUNIT_DSN']);

        $monitor->progress($_ENV['PHPUNIT_MONITOR_ID']);
        $this->assertArrayHasKey(CronMonitorStatus::InProgress->value, $monitor->getData());
        $this->assertArrayHasKey('id', $monitor->getDataProgress());

        $monitor->ok();
        $this->assertArrayHasKey(CronMonitorStatus::Ok->value, $monitor->getData());
        $this->assertArrayHasKey('id', $monitor->getDataOk());

        $this->assertCount(0, $monitor->getErrors());
    }

    /**
     * @depends testEnvironmentVariables
     */
    public function testCronMonitorProgressAndErrorWithSleep() : void
    {
        $monitor = new CronMonitor($_ENV['PHPUNIT_DSN']);

        $monitor->progress($_ENV['PHPUNIT_MONITOR_ID']);
        sleep(5);

        $monitor->error();
        $this->assertArrayHasKey(CronMonitorStatus::Error->value, $monitor->getData());
        $this->assertArrayHasKey('id', $monitor->getDataError());

        $this->assertEquals($monitor->getTimeSeconds(), $monitor->getDataError()['duration']);
    }

    /**
     * @depends testEnvironmentVariables
     */
    public function testCronMonitorWithOkException() : void
    {
        $this->expectException(CronMonitorException::class);

        $monitor = new CronMonitor($_ENV['PHPUNIT_DSN']);

        $monitor->ok();
    }

    /**
     * @depends testEnvironmentVariables
     */
    public function testCronMonitorWithErrorException() : void
    {
        $this->expectException(CronMonitorException::class);

        $monitor = new CronMonitor($_ENV['PHPUNIT_DSN']);

        $monitor->error();
    }

    public function testCronMonitorTime() : void
    {
        $monitor = new CronMonitor($_ENV['PHPUNIT_DSN']);

        $this->assertNull($monitor->getTime());
        $this->assertNull($monitor->getTimeEnd());
        $this->assertNull($monitor->getTimeSeconds());

        $monitor->progress($_ENV['PHPUNIT_MONITOR_ID']);

        $this->assertIsInt($monitor->getTime());
        $this->assertNull($monitor->getTimeEnd());
        $this->assertNull($monitor->getTimeSeconds());

        sleep(2);

        $monitor->ok();
        $this->assertTrue($monitor->getTimeEnd() > $monitor->getTime());
        $this->assertTrue($monitor->getTimeSeconds() >= 2);
        $this->assertIsInt($monitor->getTime());
        $this->assertIsInt($monitor->getTimeEnd());
        $this->assertIsInt($monitor->getTimeSeconds());
    }
}
