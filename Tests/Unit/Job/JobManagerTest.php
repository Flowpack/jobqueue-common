<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Job;

/*
 * This file is part of the TYPO3.Jobqueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Jobqueue\Common\Job\JobManager;
use TYPO3\Jobqueue\Common\Queue\QueueManager;
use TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestJob;

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
                    'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
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
        $this->assertInstanceOf('TYPO3\Jobqueue\Common\Queue\Message', $message);
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
