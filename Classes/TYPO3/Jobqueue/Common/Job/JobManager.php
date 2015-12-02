<?php
namespace TYPO3\Jobqueue\Common\Job;

/*
 * This file is part of the TYPO3.Jobqueue.Common package.
 *
 * (c) Contributors to the package
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Jobqueue\Common\Exception as JobQueueException;
use TYPO3\Jobqueue\Common\Queue\Message;
use TYPO3\Jobqueue\Common\Queue\QueueManager;

/**
 * Job manager
 */
class JobManager
{
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
    public function queue($queueName, JobInterface $job)
    {
        $queue = $this->queueManager->getQueue($queueName);

        $payload = serialize($job);
        $message = new Message($payload);

        $queue->submit($message);
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
    public function waitAndExecute($queueName, $timeout = null)
    {
        $queue = $this->queueManager->getQueue($queueName);
        $message = $queue->waitAndReserve($timeout);
        if ($message !== null) {
            $job = unserialize($message->getPayload());

            try {
                $success = $job->execute($queue, $message);
                $queue->finish($message);
            } catch (\Exception $exception) {
                $queue->finish($message);
                throw new JobQueueException('Job execution for "' . $message->getIdentifier() . '" threw an exception', 1446806185, $exception);
            }

            if ($success) {
                return $job;
            } else {
                throw new JobQueueException('Job execution for "' . $message->getIdentifier() . '" failed', 1334056583);
            }
        }

        return null;
    }

    /**
     *
     * @param string $queueName
     * @param integer $limit
     * @return array
     */
    public function peek($queueName, $limit = 1)
    {
        $queue = $this->queueManager->getQueue($queueName);
        $messages = $queue->peek($limit);
        return array_map(function (Message $message) {
            $job = unserialize($message->getPayload());
            return $job;
        }, $messages);
    }
}
