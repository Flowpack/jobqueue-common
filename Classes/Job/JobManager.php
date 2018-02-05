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
use Neos\Cache\Frontend\VariableFrontend;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Booting\Scripts;
use Neos\Flow\Property\PropertyMapper;
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
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $messageCache;

    /**
     * @Flow\InjectConfiguration
     * @var array
     */
    protected $settings;

    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array
     */
    protected $flowSettings;

    /**
     * Put a job in the queue
     *
     * @param string $queueName
     * @param JobInterface $job The job to submit to the queue
     * @param array $options Simple key/value array with options that will be passed to the queue for this job (optional)
     * @return void
     * @api
     */
    public function queue($queueName, JobInterface $job, array $options = [])
    {
        $queue = $this->queueManager->getQueue($queueName);

        $payload = serialize($job);
        $messageId = $queue->submit($payload, $options);
        $this->emitMessageSubmitted($queue, $messageId, $payload, $options);
    }

    /**
     * Wait for a job in the given queue and execute it
     * A worker using this method should catch exceptions
     *
     * @param string $queueName
     * @param integer $timeout
     * @return Message The message that was processed or NULL if no job was executed and a timeout occurred
     * @throws \Exception
     * @api
     */
    public function waitAndExecute($queueName, $timeout = null)
    {
        $queue = $this->queueManager->getQueue($queueName);
        $message = $queue->waitAndReserve($timeout);
        if ($message === null) {
            $this->emitMessageTimeout($queue);
            // timeout
            return null;
        }
        $this->emitMessageReserved($queue, $message);

        $queueSettings = $this->queueManager->getQueueSettings($queueName);
        try {
            if (isset($queueSettings['executeIsolated']) && $queueSettings['executeIsolated'] === true) {
                $messageCacheIdentifier = sha1(serialize($message));
                $this->messageCache->set($messageCacheIdentifier, serialize($message));
                Scripts::executeCommand('flowpack.jobqueue.common:job:execute', $this->flowSettings, false, [$queue->getName(), $messageCacheIdentifier]);
                $this->messageCache->remove($messageCacheIdentifier);
            } else {
                $this->executeJobForMessage($queue, $message);
            }
        } catch (\Exception $exception) {
            $maximumNumberOfReleases = isset($queueSettings['maximumNumberOfReleases']) ? (integer)$queueSettings['maximumNumberOfReleases'] : 0;
            if ($message->getNumberOfReleases() < $maximumNumberOfReleases) {
                $releaseOptions = isset($queueSettings['releaseOptions']) ? $queueSettings['releaseOptions'] : [];
                $queue->release($message->getIdentifier(), $releaseOptions);
                $this->emitMessageReleased($queue, $message, $releaseOptions, $exception);
                throw new JobQueueException(sprintf('Job execution for job (message: "%s", queue: "%s") failed (%d/%d trials) - RELEASE', $message->getIdentifier(), $queue->getName(), $message->getNumberOfReleases() + 1, $maximumNumberOfReleases + 1), 1334056583, $exception);
            } else {
                $queue->abort($message->getIdentifier());
                $this->emitMessageFailed($queue, $message, $exception);
                throw new JobQueueException(sprintf('Job execution for job (message: "%s", queue: "%s") failed (%d/%d trials) - ABORTING', $message->getIdentifier(), $queue->getName(), $message->getNumberOfReleases() + 1, $maximumNumberOfReleases + 1), 1334056584, $exception);
            }
        }

        $queue->finish($message->getIdentifier());
        $this->emitMessageFinished($queue, $message);

        return $message;
    }

    /**
     * @param QueueInterface $queue
     * @param Message $message
     * @return void
     * @throws JobQueueException
     * @internal This method has to be public so that it can be run from the command handler (when "executeIsolated" is set). It is not meant to be called from "user land"
     */
    public function executeJobForMessage(QueueInterface $queue, Message $message)
    {
        // TODO stabilize unserialize() call (maybe using PHPs unserialize_callback_func directive)
        $job = unserialize($message->getPayload());
        if (!$job instanceof JobInterface) {
            throw new \RuntimeException(sprintf('The message "%s" in queue "%s" could not be unserialized to a class implementing JobInterface', $message->getIdentifier(), $queue->getName()), 1465901245);
        }
        $jobExecutionSuccess = $job->execute($queue, $message);
        if (!$jobExecutionSuccess) {
            throw new JobQueueException(sprintf('execute() for job "%s" did not return TRUE', $job->getLabel()), 1468927872);
        }
    }

    /**
     *
     * @param string $queueName
     * @param integer $limit
     * @return JobInterface[]
     * @api
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
     * Signal that is triggered when a message has been submitted to a queue
     *
     * @param QueueInterface $queue The queue a message was submitted to
     * @param string $messageId The unique id of the message that was submitted (determined by the queue implementation)
     * @param mixed $payload The serialized job that has been added to a queue
     * @param array $options Optional array of options passed to JobManager::queue()
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitMessageSubmitted(QueueInterface $queue, $messageId, $payload, array $options = [])
    {
    }

    /**
     * Signal that is triggered when a message could not be reserved (probably due to a timeout)
     *
     * @param QueueInterface $queue The queue that returned with a timeout
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitMessageTimeout(QueueInterface $queue)
    {
    }

    /**
     * Signal that is triggered when a message was reserved
     *
     * @param QueueInterface $queue The queue the reserved message belongs to
     * @param Message $message The message that was reserved
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitMessageReserved(QueueInterface $queue, Message $message)
    {
    }

    /**
     * Signal that is triggered when a message has been processed successfully
     *
     * @param QueueInterface $queue The queue the finished message belongs to
     * @param Message $message The message that was finished successfully
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitMessageFinished(QueueInterface $queue, Message $message)
    {
    }

    /**
     * Signal that is triggered when a message has been re-released to the queue
     *
     * @param QueueInterface $queue The queue the released message belongs to
     * @param Message $message The message that was released to the queue again
     * @param array $releaseOptions The options that were passed to the release call
     * @param \Exception $jobExecutionException The exception (if any) thrown by the job execution
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitMessageReleased(QueueInterface $queue, Message $message, array $releaseOptions, \Exception $jobExecutionException = null)
    {
    }

    /**
     * Signal that is triggered when processing of a message failed
     *
     * @param QueueInterface $queue The queue the failed message belongs to
     * @param Message $message The message that could not be executed successfully
     * @param \Exception $jobExecutionException The exception (if any) thrown by the job execution
     * @return void
     * @Flow\Signal
     * @api
     */
    protected function emitMessageFailed(QueueInterface $queue, Message $message, \Exception $jobExecutionException = null)
    {
    }

}
