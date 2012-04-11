<?php
namespace Jobqueue\Common\Queue;

/*                                                                        *
 * This script belongs to the FLOW3 package "Jobqueue.Common".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Queue manager
 */
class QueueManager {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $queues = array();

	/**
	 * @param array $settings
	 * @return void
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 *
	 * @param string $queueName
	 * @return \Jobqueue\Common\Queue\QueueInterface
	 */
	public function getQueue($queueName) {
		if (isset($this->queues[$queueName])) {
			return $this->queues[$queueName];
		}

		if (!isset($this->settings['queues'][$queueName])) {
			throw new \TYPO3\FLOW3\Exception('Queue "' . $queueName . '" is not configured', 1334054137);
		}
		if (!isset($this->settings['queues'][$queueName]['className'])) {
			throw new \TYPO3\FLOW3\Exception('Option className for queue "' . $queueName . '" is not configured', 1334147126);
		}
		$queueObjectName = $this->settings['queues'][$queueName]['className'];
		$options = isset($this->settings['queues'][$queueName]['options']) ? $this->settings['queues'][$queueName]['options'] : array();
		$queue = new $queueObjectName($queueName, $options);

		$this->queues[$queueName] = $queue;

		return $queue;
	}

}
?>