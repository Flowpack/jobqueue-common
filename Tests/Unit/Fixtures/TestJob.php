<?php
namespace Jobqueue\Common\Tests\Unit\Fixtures;

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
 * Test job
 */
class TestJob extends \Jobqueue\Common\Job\AbstractJob {

	/**
	 * @var boolean
	 */
	protected $processed = FALSE;

	/**
	 * Do nothing
	 *
	 * @param \Jobqueue\Common\Queue\QueueInterface $queue
	 * @return boolean
	 */
	public function execute(\Jobqueue\Common\Queue\QueueInterface $queue) {
		$this->processed = TRUE;
		return TRUE;
	}

	/**
	 * @return boolean
	 */
	public function getProcessed() {
		return $this->processed;
	}

}
?>