<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Queue;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Jobqueue.Common". *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Jobqueue\Common\Queue\QueueManager;
use TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue;

/**
 * Queue manager
 */
class QueueManagerTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function getQueueCreatesInstanceByQueueName() {
		$queueManager = new QueueManager();
		$queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
				)
			)
		));

		$queue = $queueManager->getQueue('TestQueue');
		$this->assertInstanceOf('TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue', $queue);
	}

	/**
	 * @test
	 */
	public function getQueueSetsOptionsOnInstance() {
		$queueManager = new QueueManager();
		$queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue',
					'options' => array(
						'foo' => 'bar'
					)
				)
			)
		));

		/** @var TestQueue $queue */
		$queue = $queueManager->getQueue('TestQueue');
		$this->assertEquals(array('foo' => 'bar'), $queue->getOptions());
	}

	/**
	 * @test
	 */
	public function getQueueReusesInstances() {
		$queueManager = new QueueManager();
		$queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'className' => 'TYPO3\Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
				)
			)
		));

		$queue = $queueManager->getQueue('TestQueue');
		$this->assertSame($queue, $queueManager->getQueue('TestQueue'));
	}

}
?>