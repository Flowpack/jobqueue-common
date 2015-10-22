<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Job;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Jobqueue.Common". *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

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
