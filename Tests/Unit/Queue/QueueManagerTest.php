<?php
namespace Jobqueue\Common\Tests\Unit\Queue;

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
class QueueManagerTest extends \TYPO3\FLOW3\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getQueueCreatesInstanceByQueueName() {
		$queueManager = new \Jobqueue\Common\Queue\QueueManager();
		$queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'type' => 'Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
				)
			)
		));

		$queue = $queueManager->getQueue('TestQueue');
		$this->assertInstanceOf('Jobqueue\Common\Tests\Unit\Fixtures\TestQueue', $queue);
	}

	/**
	 * @test
	 */
	public function getQueueSetsOptionsOnInstance() {
		$queueManager = new \Jobqueue\Common\Queue\QueueManager();
		$queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'type' => 'Jobqueue\Common\Tests\Unit\Fixtures\TestQueue',
					'options' => array(
						'foo' => 'bar'
					)
				)
			)
		));

		$queue = $queueManager->getQueue('TestQueue');
		$this->assertEquals(array('foo' => 'bar'), $queue->getOptions());
	}

	/**
	 * @test
	 */
	public function getQueueReusesInstances() {
		$queueManager = new \Jobqueue\Common\Queue\QueueManager();
		$queueManager->injectSettings(array(
			'queues' => array(
				'TestQueue' => array(
					'type' => 'Jobqueue\Common\Tests\Unit\Fixtures\TestQueue'
				)
			)
		));

		$queue = $queueManager->getQueue('TestQueue');
		$this->assertSame($queue, $queueManager->getQueue('TestQueue'));
	}

}
?>