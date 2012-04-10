<?php
namespace Jobqueue\Common\Tests\Unit\Job;

/*                                                                        *
 * This script belongs to the FLOW3 package "Jobqueue.Common".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Queue manager
 */
class JobManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @var \Jobqueue\Common\Queue\QueueManager
	 */
	protected $queueManager;

	/**
	 * @var \Jobqueue\Common\Job\JobManager
	 */
	protected $jobManager;

	/**
	 *
	 */
	public function setUp() {
		$this->queueManager = new \Jobqueue\Common\Queue\QueueManager();
		$this->queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'type' => 'Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
				)
			)
		));

		$this->jobManager = new \Jobqueue\Common\Job\JobManager();
		\TYPO3\FLOW3\Reflection\ObjectAccess::setProperty($this->jobManager, 'queueManager', $this->queueManager, TRUE);
	}

	/**
	 * @test
	 */
	public function queuePublishesMessageToQueue() {
		$job = new \Jobqueue\Common\Tests\Unit\Fixtures\TestJob();
		$this->jobManager->queue('TestQueue', $job);

		$testQueue = $this->queueManager->getQueue('TestQueue');
		$message = $testQueue->peek();
		$this->assertInstanceOf('Jobqueue\Common\Queue\Message', $message);
	}

	/**
	 * @test
	 */
	public function waitAndExecuteGetsAndExecutesJobFromQueue() {
		$job = new \Jobqueue\Common\Tests\Unit\Fixtures\TestJob();
		$this->jobManager->queue('TestQueue', $job);

		$queuedJob = $this->jobManager->waitAndExecute('TestQueue');
		$this->assertTrue($queuedJob->getProcessed());
	}

}
?>