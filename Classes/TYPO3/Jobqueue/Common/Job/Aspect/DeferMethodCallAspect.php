<?php
namespace TYPO3\Jobqueue\Common\Job\Aspect;

/*                                                                        *
 * This script belongs to the FLOW3 package "Jobqueue.Common".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Defer method call aspect
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class DeferMethodCallAspect {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Jobqueue\Common\Job\JobManager
	 */
	protected $jobManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var boolean
	 */
	protected $processingJob = FALSE;

	/**
	 * @param \TYPO3\Flow\Aop\JoinPointInterface $joinPoint The current join point
	 * @return void
	 * @Flow\Around("methodAnnotatedWith(TYPO3\Jobqueue\Common\Annotations\Defer)")
	 */
	public function queueMerthodCallAsJob(\TYPO3\Flow\Aop\JoinPointInterface $joinPoint) {
		if ($this->processingJob) {
			return $joinPoint->getAdviceChain()->proceed($joinPoint);
		} else {
			$deferAnnotation = $this->reflectionService->getMethodAnnotation($joinPoint->getClassName(), $joinPoint->getMethodName(), 'TYPO3\Jobqueue\Common\Annotations\Defer');
			$queueName = $deferAnnotation->queueName;
			$job = new \TYPO3\Jobqueue\Common\Job\StaticMethodCallJob($joinPoint->getClassName(), $joinPoint->getMethodName(), $joinPoint->getMethodArguments());
			$this->jobManager->queue($queueName, $job);
			return NULL;
		}
	}

	/**
	 * @param boolean $processingJob
	 */
	public function setProcessingJob($processingJob) {
		$this->processingJob = $processingJob;
	}

}
?>