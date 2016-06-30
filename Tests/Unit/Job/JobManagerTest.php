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
use TYPO3\Flow\Tests\UnitTestCase;
use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Queue\QueueManager;
use Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestJob;

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
     * @var QueueManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockQueueManager;

    /**
     * @var TestQueue
     */
    protected $testQueue;


    public function setUp()
    {
        $this->mockQueueManager = $this->getMockBuilder(QueueManager::class)->disableOriginalConstructor()->getMock();
        $this->testQueue = new TestQueue('TestQueue');
        $this->mockQueueManager->expects($this->any())->method('getQueue')->with('TestQueue')->will($this->returnValue($this->testQueue));

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
        $this->assertNotNull($messageId);
    }

    /**
     * @test
     */
    public function queuePassesOptionsToQueue()
    {
        $mockOptions = ['foo' => 'Bar', 'baz' => 'Foos'];
        $job = new TestJob();
        $this->jobManager->queue('TestQueue', $job, $mockOptions);

        $this->assertSame($mockOptions, $this->testQueue->getLastSubmitOptions());
    }

    /**
     * @test
     */
    public function waitAndExecuteGetsAndExecutesJobFromQueue()
    {
        $job = new TestJob();
        $this->jobManager->queue('TestQueue', $job);

        /** @var TestJob $queuedJob */
        $queuedJob = $this->jobManager->waitAndExecute('TestQueue');
        $this->assertTrue($queuedJob->isProcessed());
    }
}
