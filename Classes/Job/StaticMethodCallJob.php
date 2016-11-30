<?php
namespace Flowpack\JobQueue\Common\Job;

/*
 * This file is part of the Flowpack.JobQueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use Neos\Utility\TypeHandling;

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
            call_user_func_array([$service, $methodName], $this->arguments);
            return true;
        } catch (\Exception $exception) {
            $this->deferMethodCallAspect->setProcessingJob(false);
            throw $exception;
        }
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        $arguments = [];
        foreach($this->arguments as $argumentValue) {
            if (TypeHandling::isSimpleType($argumentValue)) {
                $arguments[] = $argumentValue;
            } else {
                $arguments[] = '[' . gettype($argumentValue) . ']';
            }
        }
        return sprintf('%s::%s(%s)', $this->className, $this->methodName, implode(', ', $arguments));
    }
}
