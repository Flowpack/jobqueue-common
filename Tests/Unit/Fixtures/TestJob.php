<?php
namespace TYPO3\Jobqueue\Common\Tests\Unit\Fixtures;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Jobqueue.Common". *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Jobqueue\Common\Job\JobInterface;
use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueInterface;

/**
 * Test job
 */
class TestJob implements JobInterface {

	/**
	 * @var boolean
	 */
	protected $processed = FALSE;

	/**
	 * Do nothing
	 *
	 * @param QueueInterface $queue
	 * @param Message $message
	 * @return boolean
	 */
	public function execute(QueueInterface $queue, Message $message) {
		$this->processed = TRUE;
		return TRUE;
	}

	/**
	 * @return boolean
	 */
	public function getProcessed() {
		return $this->processed;
	}

	/**
	 * Get an optional identifier for the job
	 *
	 * @return string A job identifier
	 */
	public function getIdentifier() {
		return 'testjob';
	}

	/**
	 * Get a readable label for the job
	 *
	 * @return string A label for the job
	 */
	public function getLabel() {
		return 'Test Job';
	}
}