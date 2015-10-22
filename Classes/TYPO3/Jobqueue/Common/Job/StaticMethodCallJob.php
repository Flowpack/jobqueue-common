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
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueInterface;

/**
 * Static method call job
 */
class StaticMethodCallJob implements JobInterface
{
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
     * @Flow\Inject
     * @var Aspect\DeferMethodCallAspect
     */
    protected $deferMethodCallAspect;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     *
     * @param string $className
     * @param string $methodName
     * @param array $arguments
     */
    public function __construct($className, $methodName, array $arguments)
    {
        $this->className = $className;
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }

    /**
     * Execute the job
     *
     * A job should finish itself after successful execution using the queue methods.
     *
     * @param QueueInterface $queue
     * @param Message $message
     * @return boolean TRUE If the execution was successful
     * @throws \Exception
     */
    public function execute(QueueInterface $queue, Message $message)
    {
        $service = $this->objectManager->get($this->className);
        $this->deferMethodCallAspect->setProcessingJob(true);
        try {
            $methodName = $this->methodName;
            call_user_func_array(array($service, $methodName), $this->arguments);
            return true;
        } catch (\Exception $exception) {
            $this->deferMethodCallAspect->setProcessingJob(false);
            throw $exception;
        }
        $this->deferMethodCallAspect->setProcessingJob(false);
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->className . '->' . $this->methodName;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return null;
    }
}
