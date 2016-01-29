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

use TYPO3\Flow\Reflection\ObjectAccess;
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
     * @var QueueManager
     */
    protected $queueManager;

    /**
     * @var JobManager
     */
    protected $jobManager;

    /**
     *
     */
    public function setUp()
    {
        $this->queueManager = new QueueManager();
        $this->queueManager->injectSettings(array(
            'queues' => array(
                'TestQueue' => array(
                    'className' => 'Flowpack\JobQueue\Common\Tests\Unit\Fixtures\TestQueue'
                )
            )
        ));

        $this->jobManager = new JobManager();
        ObjectAccess::setProperty($this->jobManager, 'queueManager', $this->queueManager, true);
    }

    /**
     * @test
     */
    public function queuePublishesMessageToQueue()
    {
        $job = new TestJob();
        $this->jobManager->queue('TestQueue', $job);

        $testQueue = $this->queueManager->getQueue('TestQueue');
        $message = $testQueue->peek();
        $this->assertInstanceOf('Flowpack\JobQueue\Common\Queue\Message', $message);
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
        $this->assertTrue($queuedJob->getProcessed());
    }
}
