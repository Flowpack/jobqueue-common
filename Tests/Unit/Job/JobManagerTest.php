<?php
namespace Flowpack\JobQueue\Common\Tests\Unit\Job;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue;
use Neos\Flow\Tests\UnitTestCase;
use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestJob;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the JobManager
 */
class JobManagerTest extends UnitTestCase
{
    /**
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @var QueueManager|MockObject
     */
    protected $mockQueueManager;

    /**
     * @var TestQueue
     */
    protected $testQueue;


    public function setUp(): void
    {
        $this->mockQueueManager = $this->getMockBuilder(QueueManager::class)->disableOriginalConstructor()->getMock();
        $this->testQueue = new TestQueue('TestQueue');
        $this->mockQueueManager->expects($this->any())->method('getQueue')->with('TestQueue')->will(self::returnValue($this->testQueue));

        $this->jobManager = new JobManager();
        $this->inject($this->jobManager, 'queueManager', $this->mockQueueManager);
    }

    /**
     * @test
     */
    public function queueSubmitsMessageToQueue()
    {
        $job = new TestJob();
        $this->jobManager->queue('TestQueue', $job);

        $messageId = $this->testQueue->peek();
        self::assertNotNull($messageId);
    }

    /**
     * @test
     */
    public function queuePassesOptionsToQueue()
    {
        $mockOptions = ['foo' => 'Bar', 'baz' => 'Foos'];
        $job = new TestJob();
        $this->jobManager->queue('TestQueue', $job, $mockOptions);

        self::assertSame($mockOptions, $this->testQueue->getLastSubmitOptions());
    }
}
