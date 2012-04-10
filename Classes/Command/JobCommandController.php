<?php
namespace Jobqueue\Common\Command;

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
 * Job command controller
 */
class JobCommandController extends \TYPO3\FLOW3\MVC\Controller\CommandController {

	/**
	 * @FLOW3\Inject
	 * @var \Jobqueue\Common\Job\JobManager
	 */
	protected $jobManager;

	/**
	 * Work on a queue and execute jobs
	 *
	 * @param string $queueName The name of the queue
	 * @return void
	 */
	public function workCommand($queueName) {
		do {
			$this->jobManager->waitAndExecute($queueName);
		} while (TRUE);
	}

}
?>