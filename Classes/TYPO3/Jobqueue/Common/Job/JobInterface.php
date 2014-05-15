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
use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueInterface;

/**
 * Job interface
 */
interface JobInterface {

	/**
	 * Execute the job
	 *
	 * A job should finish itself after successful execution using the queue methods.
	 *
	 * @param QueueInterface $queue
	 * @param Message $message The original message
	 * @return boolean TRUE if the job was executed successfully and the message should be finished
	 */
	public function execute(QueueInterface $queue, Message $message);

	/**
	 * Get an optional identifier for the job
	 *
	 * @return string A job identifier
	 */
	public function getIdentifier();

	/**
	 * Get a readable label for the job
	 *
	 * @return string A label for the job
	 */
	public function getLabel();

}