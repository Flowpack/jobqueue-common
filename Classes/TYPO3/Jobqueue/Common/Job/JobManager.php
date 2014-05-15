<?php
namespace TYPO3\Jobqueue\Common\Job;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Jobqueue.Common". *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Jobqueue\Common\Exception as JobQueueException;
use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueManager;

/**
 * Job manager
 */
class JobManager {

	/**
	 * @Flow\Inject
	 * @var QueueManager
	 */
	protected $queueManager;

	/**
	 * @Flow\Inject
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * Put a job in the queue
	 *
	 * @param string $queueName
	 * @param JobInterface $job
	 * @return void
	 */
	public function queue($queueName, JobInterface $job) {
		$queue = $this->queueManager->getQueue($queueName);

		$payload = serialize($job);
		$message = new Message($payload);

		$queue->publish($message);
	}

	/**
	 * Wait for a job in the given queue and execute it
	 * A worker using this method should catch exceptions
	 *
	 * @param string $queueName
	 * @param integer $timeout
	 * @return JobInterface The job that was executed or NULL if no job was executed and a timeout occured
	 * @throws JobQueueException
	 */
	public function waitAndExecute($queueName, $timeout = NULL) {
		$queue = $this->queueManager->getQueue($queueName);
		$message = $queue->waitAndReserve($timeout);
		if ($message !== NULL) {
			$job = unserialize($message->getPayload());

			if ($job->execute($queue, $message)) {
				$queue->finish($message);
				return $job;
			} else {
				throw new JobQueueException('Job execution for "' . $message->getIdentifier() . '" failed', 1334056583);
			}
		}

		return NULL;
	}

	/**
	 *
	 * @param string $queueName
	 * @param integer $limit
	 * @return array
	 */
	public function peek($queueName, $limit = 1) {
		$queue = $this->queueManager->getQueue($queueName);
		$messages = $queue->peek($limit);
		return array_map(function(Message $message) {
			$job = unserialize($message->getPayload());
			return $job;
		}, $messages);
	}

}