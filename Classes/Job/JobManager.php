<?php
namespace Jobqueue\Common\Job;

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
 * Job manager
 */
class JobManager {

	/**
	 * @FLOW3\Inject
	 * @var \Jobqueue\Common\Queue\QueueManager
	 */
	protected $queueManager;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Property\PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 *
	 * @param string $queueName
	 * @param \Jobqueue\Common\Job\JobInterface $job
	 * @return void
	 */
	public function queue($queueName, JobInterface $job) {
		$queue = $this->queueManager->getQueue($queueName);

		$payload = serialize($job);
		$message = new \Jobqueue\Common\Queue\Message($payload);

		$queue->publish($message);
	}

	/**
	 * Wait for a job in the given queue and execute it
	 *
	 * A worker using this method should catch exceptions
	 *
	 * @param string $queueName
	 * @return \Jobqueue\Common\Job\JobInterface The job that was executed or NULL if no job was executed and a timeout occured
	 */
	public function waitAndExecute($queueName) {
		$queue = $this->queueManager->getQueue($queueName);
		$message = $queue->waitAndReserve();
		if ($message !== NULL) {
			$job = unserialize($message->getPayload());

			$job->setMessage($message);

			if ($job->execute($queue)) {
				$queue->finish($message);
				return $job;
			} else {
				throw new \TYPO3\FLOW3\Exception('Job execution for "' . $message->getIdentifier() . '" failed', 1334056583);
			}
		}

		return NULL;
	}

}
?>