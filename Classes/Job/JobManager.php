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

use Flowpack\JobQueue\Common\Queue\QueueInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use Flowpack\JobQueue\Common\Exception as JobQueueException;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueManager;

/**
 * Job manager
 *
 * @Flow\Scope("singleton")
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
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $settings;

    /**
     * Put a job in the queue
     *
     * @param string $queueName
     * @param JobInterface $job The job to submit to the queue
     * @param array $options Simple key/value array with options that will be passed to the queue for this job (optional)
     * @return void
     */
    public function queue($queueName, JobInterface $job, array $options = [])
    {
        $queue = $this->queueManager->getQueue($queueName);

        $payload = serialize($job);
        $queue->submit($payload, $options);
    }

    /**
     * Wait for a job in the given queue and execute it
     * A worker using this method should catch exceptions
     *
     * @param string $queueName
     * @param integer $timeout
     * @return JobInterface The job that was executed or NULL if no job was executed and a timeout occurred
     * @throws JobQueueException
     */
    public function waitAndExecute($queueName, $timeout = null)
    {
        $queue = $this->queueManager->getQueue($queueName);
        $queueSettings = $this->queueManager->getQueueSettings($queueName);

        $message = $queue->waitAndReserve($timeout);
        if ($message === null) {
            $this->emitMessageTimeout($queue);
            // timeout
            return null;
        }
        $this->emitMessageReserved($message, $queue);

        // TODO stabilize unserialize() call (maybe using the unserialize_callback_func directive)
        $job = unserialize($message->getPayload());
        if (!$job instanceof JobInterface) {
            throw new \RuntimeException('The message could not be unserialized to a class implementing JobInterface', 1465901245);
        }

        $jobExecutionSuccess = false;
        $jobExecutionException = null;
        try {
            $jobExecutionSuccess = $job->execute($queue, $message);
        } catch (\Exception $exception) {
            $jobExecutionException = $exception;
        }

        if ($jobExecutionSuccess) {
            $queue->finish($message->getIdentifier());
            $this->emitMessageFinished($message, $queue);
            return $job;
        }

        $maximumNumberOfReleases = isset($queueSettings['maximumNumberOfReleases']) ? (integer)$queueSettings['maximumNumberOfReleases'] : 0;
        if ($message->getNumberOfFailures() < $maximumNumberOfReleases) {
            $releaseOptions = isset($queueSettings['releaseOptions']) ? $queueSettings['releaseOptions'] : [];
            $queue->release($message->getIdentifier(), $releaseOptions);
            $this->emitMessageReleased($message, $queue, $jobExecutionException);
            throw new JobQueueException(sprintf('Job execution for "%s" failed (%d/%d trials) - RELEASE', $message->getIdentifier(), $message->getNumberOfFailures(), $maximumNumberOfReleases), 1334056583, $jobExecutionException);
        } else {
            $queue->abort($message->getIdentifier());
            $this->emitMessageFailed($message, $queue, $jobExecutionException);
            throw new JobQueueException(sprintf('Job execution for "%s" failed (%d/%d trials) - BURY', $message->getIdentifier(), $message->getNumberOfFailures(), $maximumNumberOfReleases), 1334056584, $jobExecutionException);
        }
    }

    /**
     *
     * @param string $queueName
     * @param integer $limit
     * @return JobInterface[]
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

    /**
     * Signal that is triggered when a message could not be reserved (probably due to a timeout)
     *
     * @param QueueInterface $queue
     * @return void
     * @Flow\Signal
     */
    protected function emitMessageTimeout(QueueInterface $queue)
    {
    }

    /**
     * Signal that is triggered when a message was reserved
     *
     * @param Message $message
     * @param QueueInterface $queue
     * @return void
     * @Flow\Signal
     */
    protected function emitMessageReserved(Message $message, QueueInterface $queue)
    {
    }

    /**
     * Signal that is triggered when a message has been processed successfully
     *
     * @param Message $message
     * @param QueueInterface $queue
     * @return void
     * @Flow\Signal
     */
    protected function emitMessageFinished(Message $message, QueueInterface $queue)
    {
    }

    /**
     * Signal that is triggered when a message has been re-released to the queue
     *
     * @param Message $message
     * @param QueueInterface $queue
     * @param \Exception $jobExecutionException
     * @return void
     * @Flow\Signal
     */
    protected function emitMessageReleased(Message $message, QueueInterface $queue, \Exception $jobExecutionException = NULL)
    {
    }

    /**
     * Signal that is triggered when processing of a message failed
     *
     * @param Message $message
     * @param QueueInterface $queue
     * @param \Exception $jobExecutionException
     * @return void
     * @Flow\Signal
     */
    protected function emitMessageFailed(Message $message, QueueInterface $queue, \Exception $jobExecutionException = NULL)
    {
    }

}