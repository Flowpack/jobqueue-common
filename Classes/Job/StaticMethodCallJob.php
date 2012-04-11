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
 * Static method call job
 */
class StaticMethodCallJob implements JobInterface {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var string
	 */
	protected $methodName;

	/**
	 * @var array
	 */
	protected $arguments;

	/**
	 * @FLOW3\Inject
	 * @var \Jobqueue\Common\Job\Aspect\DeferMethodCallAspect
	 */
	protected $deferMethodCallAspect;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 *
	 * @param string $className
	 * @param string $methodName
	 * @param array $arguments
	 */
	public function __construct($className, $methodName, array $arguments) {
		$this->className = $className;
		$this->methodName = $methodName;
		$this->arguments = $arguments;
	}

	/**
	 * Execute the job
	 *
	 * A job should finish itself after successful execution using the queue methods.
	 *
	 * @param \Jobqueue\Common\Queue\QueueInterface $queue
	 * @param \Jobqueue\Common\Queue\Message $message
	 * @return boolean TRUE If the execution was successful
	 */
	public function execute(\Jobqueue\Common\Queue\QueueInterface $queue, \Jobqueue\Common\Queue\Message $message) {
		$service = $this->objectManager->get($this->className);
		$this->deferMethodCallAspect->setProcessingJob(TRUE);
		try {
			$methodName = $this->methodName;
			call_user_func_array(array($service, $methodName), $this->arguments);
			return TRUE;
		} catch(\Exception $exception) {
			$this->deferMethodCallAspect->setProcessingJob(FALSE);
			throw $exception;
		}
		$this->deferMethodCallAspect->setProcessingJob(FALSE);
	}

	/**
	 * @return string
	 */
	public function getLabel() {
		return $this->className . '->' . $this->methodName;
	}

	/**
	 * @return string
	 */
	public function getIdentifier() {
		return NULL;
	}

}
?>