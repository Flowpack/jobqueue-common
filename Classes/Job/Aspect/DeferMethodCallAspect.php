<?php
namespace Flowpack\JobQueue\Common\Job\Aspect;

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
use Neos\Flow\Aop\JoinPointInterface;
use Neos\Flow\Reflection\ReflectionService;
use Flowpack\JobQueue\Common\Annotations\Defer;
use Flowpack\JobQueue\Common\Job\JobManager;
use Flowpack\JobQueue\Common\Job\StaticMethodCallJob;

/**
 * Defer method call aspect
 *
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class DeferMethodCallAspect
{
    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @var string
     */
    protected string $processingMethodCallHash = '';

    /**
     * @param JoinPointInterface $joinPoint The current join point
     * @return mixed
     * @Flow\Around("methodAnnotatedWith(Flowpack\JobQueue\Common\Annotations\Defer)")
     */
    public function queueMethodCallAsJob(JoinPointInterface $joinPoint)
    {
        if ($this->processingMethodCallHash === StaticMethodCallJob::methodCallHash($joinPoint->getClassName(), $joinPoint->getMethodName())) {
            return $joinPoint->getAdviceChain()->proceed($joinPoint);
        }
        /** @var Defer $deferAnnotation */
        $deferAnnotation = $this->reflectionService->getMethodAnnotation($joinPoint->getClassName(), $joinPoint->getMethodName(), Defer::class);
        $queueName = $deferAnnotation->queueName;
        $job = new StaticMethodCallJob($joinPoint->getClassName(), $joinPoint->getMethodName(), $joinPoint->getMethodArguments());
        $this->jobManager->queue($queueName, $job, $deferAnnotation->options);
        return null;
    }

    /**
     * @param string $processingMethodCallHash
     */
    public function setProcessingMethodCallHash(string $processingMethodCallHash): void
    {
        $this->processingMethodCallHash = $processingMethodCallHash;
    }
}
